<?php
// Ejecutar exclusivamente en la instancia Docker aislada de QA, con QA_AUDIT=1.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (getenv('QA_AUDIT') !== '1') throw new RuntimeException('Esta auditoría requiere QA_AUDIT=1 y una base de pruebas aislada.');

use App\Models\User;
use App\Models\Rol;
use App\Models\Departamento;
use App\Models\Encomienda;
use App\Models\ComprobantePago;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Promise\Utils;

$results=[]; $users=[]; $departamentos=[]; $gastos=[];
$suffix=bin2hex(random_bytes(6));
$beforeFiles=Storage::disk('public')->files('comprobantes');
function record(string $id, bool $pass, string $expected, $actual): void {
    global $results;
    $results[]=$r=['id'=>$id,'passed'=>$pass,'expected'=>$expected,'actual'=>$actual];
    echo json_encode($r,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
}
function browser(): Client { return new Client(['base_uri'=>'http://web','cookies'=>new CookieJar,'allow_redirects'=>false,'http_errors'=>false,'timeout'=>30]); }
function csrf(Client $c,string $url): string {
    $r=$c->get($url); preg_match('/name="_token" value="([^"]+)"/',(string)$r->getBody(),$m);
    return $m[1] ?? throw new RuntimeException("No hay CSRF en $url (HTTP {$r->getStatusCode()})");
}
function login(User $u): Client {
    $c=browser(); $r=$c->post('/login',['form_params'=>['_token'=>csrf($c,'/login'),'identifier'=>$u->email,'password'=>'AuditQA!123456']]);
    if ($r->getStatusCode()!==302) throw new RuntimeException('Login de auditoría falló');
    return $c;
}
function upload(string $token, int $amount=85000): array {
    return ['multipart'=>[
        ['name'=>'_token','contents'=>$token], ['name'=>'monto','contents'=>(string)$amount],
        ['name'=>'comprobante','contents'=>base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jVJkAAAAASUVORK5CYII='),'filename'=>'qa.png','headers'=>['Content-Type'=>'image/png']],
    ]];
}
function apiUpload(int $gasto): array {
    return ['multipart'=>[
        ['name'=>'gasto_id','contents'=>(string)$gasto],
        ['name'=>'file','contents'=>base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jVJkAAAAASUVORK5CYII='),'filename'=>'qa.png','headers'=>['Content-Type'=>'image/png']],
    ]];
}
try {
    foreach (['admin'=>'Administrador','a'=>'Residente','b'=>'Residente','conserje'=>'Conserje'] as $key=>$role) {
        $users[$key]=User::create(['nombre'=>"QA Audit $key",'email'=>"audit-$key-$suffix@example.test",'password'=>'AuditQA!123456','rol_id'=>Rol::where('nombre',$role)->value('id'),'estado'=>'activo']);
    }
    foreach (['a','b'] as $key) {
        $departamentos[$key]=Departamento::create(['numero'=>"QA-AUDIT-$key-$suffix",'piso'=>1,'estado'=>'activo']);
        $users[$key]->departamentos()->attach($departamentos[$key]->id);
        $gastos[$key]=$departamentos[$key]->gastosComunes()->create(['periodo'=>'2098-01-01','monto'=>85000,'estado'=>'pendiente']);
    }
    $admin=login($users['admin']); $a=login($users['a']); $b=login($users['b']); $guest=browser();
    $apiA = new Client(['base_uri'=>'http://web','http_errors'=>false,'headers'=>['Authorization'=>'Bearer '.$users['a']->createToken('QA API A')->plainTextToken]]);
    $apiB = new Client(['base_uri'=>'http://web','http_errors'=>false,'headers'=>['Authorization'=>'Bearer '.$users['b']->createToken('QA API B')->plainTextToken]]);
    $apiConserje = new Client(['base_uri'=>'http://web','http_errors'=>false,'headers'=>['Authorization'=>'Bearer '.$users['conserje']->createToken('QA API Conserje')->plainTextToken]]);
    $tokenA=csrf($a,'/residente/gastos-comunes'); $tokenAdmin=csrf($admin,'/admin/departamentos');
    $uploadPath='/residente/gastos-comunes/'.$gastos['a']->id.'/comprobante';
    $sinArchivo=$a->post($uploadPath,['headers'=>['Accept'=>'application/json'],'form_params'=>['_token'=>$tokenA]]);
    record('UPLOAD-01',$sinArchivo->getStatusCode()===422,'Carga web rechaza envío sin archivo',$sinArchivo->getStatusCode());
    $exceso=upload($tokenA); $exceso['headers']=['Accept'=>'application/json'];
    $exceso['multipart'][2]['contents'].=str_repeat('0',6*1024*1024);
    $r=$a->post($uploadPath,$exceso);
    record('UPLOAD-02',$r->getStatusCode()===422 && !$gastos['a']->comprobantes()->exists(),'Carga web rechaza archivos de más de 5 MB sin insertar registros',['http'=>$r->getStatusCode(),'registros'=>$gastos['a']->comprobantes()->count()]);
    $futuro=upload($tokenA); $futuro['headers']=['Accept'=>'application/json'];
    $futuro['multipart'][]=['name'=>'fecha_pago','contents'=>today()->addDay()->toDateString()];
    $r=$a->post($uploadPath,$futuro);
    record('UPLOAD-03',$r->getStatusCode()===422,'Carga web rechaza fecha de pago futura',$r->getStatusCode());
    $r=$guest->get('/api/v1/gastos-comunes'); $json=json_decode((string)$r->getBody(),true);
    $exposed=is_array($json) && in_array($gastos['b']->id,array_column($json,'id'),true);
    record('API-01',!$exposed && in_array($r->getStatusCode(),[401,403],true),'Invitado no puede leer cobros privados',['http'=>$r->getStatusCode(),'cobro_ajeno_visible'=>$exposed]);
    $r=$guest->post('/api/v1/comprobantes',apiUpload($gastos['b']->id));
    record('API-02',in_array($r->getStatusCode(),[401,403],true),'Invitado no puede cargar comprobantes en cobros ajenos',['http'=>$r->getStatusCode(),'user_id_guardado'=>ComprobantePago::where('gasto_comun_id',$gastos['b']->id)->value('user_id')]);
    $gastos['b']->update(['estado'=>'pagado']);
    $r=$apiB->post('/api/v1/comprobantes',apiUpload($gastos['b']->id));
    record('API-03',$r->getStatusCode()===422,'API autenticada rechaza comprobantes en cobros ya pagados',['http'=>$r->getStatusCode(),'comprobantes'=>ComprobantePago::where('gasto_comun_id',$gastos['b']->id)->count()]);
    $r=$guest->post('/api/v1/encomiendas',['json'=>['departamento_id'=>$departamentos['b']->id,'empresa_delivery'=>'QA No autenticado']]);
    record('API-04',in_array($r->getStatusCode(),[401,403],true),'Registrar encomiendas exige autenticación y rol autorizado',['http'=>$r->getStatusCode(),'creada'=>Encomienda::where('departamento_id',$departamentos['b']->id)->exists()]);
    $r=$apiA->get('/api/v1/gastos-comunes');
    $ids=array_column(json_decode((string)$r->getBody(),true),'id');
    record('API-05',$r->getStatusCode()===200 && $ids===[$gastos['a']->id],'Residente autenticado consulta solo sus cobros',['http'=>$r->getStatusCode(),'ids'=>$ids]);
    $r=$apiA->get('/api/v1/gastos-comunes?departamento_id='.$departamentos['b']->id);
    record('API-06',$r->getStatusCode()===200 && json_decode((string)$r->getBody(),true)===[],'Filtro ajeno no expone cobros',['http'=>$r->getStatusCode()]);
    $r=$apiA->post('/api/v1/comprobantes',apiUpload($gastos['b']->id));
    record('API-07',$r->getStatusCode()===404,'API autenticada no carga comprobantes ajenos',$r->getStatusCode());
    $r=$apiA->post('/api/v1/encomiendas',['json'=>['departamento_id'=>$departamentos['a']->id,'empresa_delivery'=>'QA Sin rol']]);
    record('API-08',$r->getStatusCode()===403,'Residente no registra encomiendas mediante API',$r->getStatusCode());
    $r=$apiConserje->get('/api/v1/gastos-comunes');
    record('API-09',$r->getStatusCode()===403,'Conserje no accede a API financiera',$r->getStatusCode());
    $r=$apiConserje->post('/api/v1/encomiendas',['json'=>['departamento_id'=>$departamentos['b']->id,'empresa_delivery'=>'QA Autorizado']]);
    $item=Encomienda::where('departamento_id',$departamentos['b']->id)->first();
    record('API-10',$r->getStatusCode()===201 && $item?->conserje_id===$users['conserje']->id,'Conserje autenticado registra autoría correcta',['http'=>$r->getStatusCode(),'autor'=>$item?->conserje_id]);
    $propio=$departamentos['a']->gastosComunes()->create(['periodo'=>'2098-04-01','monto'=>85000,'estado'=>'pendiente']);
    $payload=apiUpload($propio->id); $payload['multipart'][]=['name'=>'user_id','contents'=>(string)$users['b']->id];
    $r=$apiA->post('/api/v1/comprobantes',$payload);
    $archivo=$propio->comprobantes()->first();
    record('API-11',$r->getStatusCode()===201 && $archivo?->user_id===$users['a']->id,'API acepta comprobante propio y fija la identidad del token',['http'=>$r->getStatusCode(),'autor'=>$archivo?->user_id]);
    $r=$apiA->post('/api/v1/comprobantes',apiUpload($propio->id));
    record('API-12',$r->getStatusCode()===422 && $propio->comprobantes()->count()===1,'API bloquea un segundo comprobante en revisión',$r->getStatusCode());
    if ($archivo) {
        $r=$a->get('/residente/comprobantes/'.$archivo->id.'/archivo');
        record('WEB-03',$r->getStatusCode()===200 && (string)$r->getBody()===Storage::disk('public')->get($archivo->archivo_path),'Residente descarga su comprobante sin alterar el archivo',$r->getStatusCode());
        $r=$b->get('/residente/comprobantes/'.$archivo->id.'/archivo');
        record('WEB-04',$r->getStatusCode()===404,'Archivo de comprobante ajeno protegido',$r->getStatusCode());
    }
    $r=$a->get('/residente/gastos-comunes/'.$gastos['b']->id.'/pdf');
    record('WEB-01',$r->getStatusCode()===404,'PDF ajeno protegido con 404',$r->getStatusCode());
    $r=$a->post('/residente/gastos-comunes/'.$gastos['b']->id.'/comprobante',upload($tokenA));
    record('WEB-02',$r->getStatusCode()===404,'Carga web ajena protegida con 404',$r->getStatusCode());
    $r=$a->post('/residente/gastos-comunes/'.$gastos['a']->id.'/comprobante',upload($tokenA,1));
    $comp=ComprobantePago::where('gasto_comun_id',$gastos['a']->id)->latest('id')->firstOrFail();
    $vista=(string)$admin->get('/admin/comprobantes')->getBody();
    $doc=new DOMDocument; @$doc->loadHTML('<?xml encoding="UTF-8">'.$vista);
    $xpath=new DOMXPath($doc);
    record('PAGO-04',$xpath->query('//form[contains(@action,"/admin/comprobantes/'.$comp->id.'/aprobar")]//button[@disabled]')->length===1
        && str_contains($vista,'El monto no coincide con el total del cobro.'),'Interfaz bloquea aprobar un monto distinto y explica el motivo', 'Botón deshabilitado y aviso');
    $r=$admin->patch('/admin/comprobantes/'.$comp->id.'/aprobar',['form_params'=>['_token'=>$tokenAdmin]]);
    record('PAGO-01',$gastos['a']->fresh()->estado!=='pagado' && $comp->fresh()->estado==='en_revision','Un comprobante de $1 no liquida un cobro de $85.000',['http'=>$r->getStatusCode(),'monto_comprobante'=>$comp->monto,'monto_cobro'=>$gastos['a']->monto,'estado_cobro'=>$gastos['a']->fresh()->estado]);
    $grande=$departamentos['a']->gastosComunes()->create(['periodo'=>'2098-02-01','monto'=>999999999999,'estado'=>'pendiente']);
    $r=$a->post('/residente/gastos-comunes/'.$grande->id.'/comprobante',upload($tokenA,999999999999));
    record('PAGO-02',$r->getStatusCode()===302 && $grande->comprobantes()->exists(),'Comprobante admite el mismo monto máximo que el gasto común',['http'=>$r->getStatusCode(),'registro_creado'=>$grande->comprobantes()->exists()]);
    $race=$departamentos['a']->gastosComunes()->create(['periodo'=>'2098-03-01','monto'=>85000,'estado'=>'pendiente']);
    $otraSesion=login($users['a']); $token2=csrf($otraSesion,'/residente/gastos-comunes');
    $racePath='/residente/gastos-comunes/'.$race->id.'/comprobante';
    $responses=Utils::unwrap([$a->postAsync($racePath,upload($tokenA)),$otraSesion->postAsync($racePath,upload($token2))]);
    record('PAGO-03',$race->comprobantes()->where('estado','en_revision')->count()===1,'Dos cargas simultáneas dejan un único comprobante en revisión',['http'=>array_map(fn($r)=>$r->getStatusCode(),$responses),'en_revision'=>$race->comprobantes()->count()]);
    $r=$admin->post('/admin/departamentos/'.$departamentos['a']->id.'/contactos',['headers'=>['Accept'=>'application/json'],'form_params'=>['_token'=>$tokenAdmin,'nombre'=>'Contacto inválido QA','telefono'=>'+56912345678','user_id'=>$users['b']->id]]);
    $contacto=$departamentos['a']->contactosEmergencia()->first();
    record('DEPTO-01',!$contacto || $contacto->user_id!==$users['b']->id,'Contacto del departamento A no puede asociarse a un residente de B',['http'=>$r->getStatusCode(),'residente_ajeno_aceptado'=>(bool)$contacto]);
    $r=$admin->post('/admin/departamentos/'.$departamentos['a']->id.'/contactos',['headers'=>['Accept'=>'application/json'],'form_params'=>['_token'=>$tokenAdmin,'nombre'=>'Contacto inválido QA','telefono'=>'-------','user_id'=>$users['a']->id]]);
    $contacto=$departamentos['a']->contactosEmergencia()->first();
    record('DEPTO-02',!$contacto,'Teléfono de contacto requiere dígitos reales',['http'=>$r->getStatusCode(),'telefono_guardado'=>$contacto?->telefono]);
    $r=$admin->post('/admin/departamentos/'.$departamentos['a']->id.'/contactos',['form_params'=>['_token'=>$tokenAdmin,'nombre'=>'Contacto válido QA','telefono'=>'+56 9 1234 5678','user_id'=>$users['a']->id]]);
    $contacto=$departamentos['a']->contactosEmergencia()->first();
    record('DEPTO-03',$r->getStatusCode()===302 && $contacto?->user_id===$users['a']->id,'Contacto válido de un residente de la misma unidad se guarda',$r->getStatusCode());
    foreach (['/admin/avisos?busqueda%5B%5D=x','/residente/mural?busqueda%5B%5D=x','/admin/comprobantes?departamento_id%5B%5D=1'] as $url) {
        $client=str_starts_with($url,'/admin')?$admin:$a; $r=$client->get($url);
        record('FILTRO-'.$url,$r->getStatusCode()<500,'Entrada malformada no debe generar error 500',['url'=>$url,'http'=>$r->getStatusCode()]);
    }
    $access=$users['a']->createToken('QA Reset'); $tokenId=$access->accessToken->id;
    $reset=str_repeat('a',64);
    DB::table('password_reset_tokens')->updateOrInsert(['email'=>$users['a']->email],['token'=>Hash::make($reset),'created_at'=>now()]);
    $r=$guest->post('/restablecer-contrasena',['form_params'=>['_token'=>csrf($guest,'/login'),'email'=>$users['a']->email,'token'=>$reset,'password'=>'123456','password_confirmation'=>'123456']]);
    record('AUTH-01',!Hash::check('123456',$users['a']->fresh()->password),'Recuperación mantiene el mínimo de 12 caracteres del cambio de contraseña',['http'=>$r->getStatusCode(),'clave_de_6_aceptada'=>Hash::check('123456',$users['a']->fresh()->password)]);
    foreach ([str_repeat('a',73),str_repeat('á',37),"SeguraAudit!123\0"] as $invalida) {
        $r=$guest->post('/restablecer-contrasena',['headers'=>['Accept'=>'application/json'],'form_params'=>['_token'=>csrf($guest,'/login'),'email'=>$users['a']->email,'token'=>$reset,'password'=>$invalida,'password_confirmation'=>$invalida]]);
        record('AUTH-LIMITE-'.strlen($invalida),$r->getStatusCode()===422,'Recuperación rechaza más de 72 bytes o caracteres nulos',$r->getStatusCode());
    }
    $r=$guest->post('/restablecer-contrasena',['form_params'=>['_token'=>csrf($guest,'/login'),'email'=>$users['a']->email,'token'=>$reset,'password'=>'NuevaAudit!123456','password_confirmation'=>'NuevaAudit!123456']]);
    record('AUTH-03',Hash::check('NuevaAudit!123456',$users['a']->fresh()->password),'Recuperación acepta contraseña válida con token correcto',$r->getStatusCode());
    record('AUTH-02',!DB::table('personal_access_tokens')->where('id',$tokenId)->exists(),'Restablecer contraseña revoca tokens API anteriores',['token_anterior_conservado'=>DB::table('personal_access_tokens')->where('id',$tokenId)->exists()]);
    $revocada=$apiA->get('/api/v1/gastos-comunes')->getStatusCode();
    record('AUTH-04',$revocada===401,'Token previo al restablecimiento ya no accede a la API',$revocada);
    $r=$a->get('/perfil');
    record('AUTH-05',$r->getStatusCode()===302,'Sesión anterior al restablecimiento pierde acceso al perfil',$r->getStatusCode());
    $hash=$users['a']->fresh()->password;
    $r=$guest->post('/restablecer-contrasena',['headers'=>['Accept'=>'application/json'],'form_params'=>['_token'=>csrf($guest,'/login'),'email'=>$users['a']->email,'token'=>$reset,'password'=>'OtraAudit!123456','password_confirmation'=>'OtraAudit!123456']]);
    record('AUTH-06',$r->getStatusCode()===422 && $users['a']->fresh()->password===$hash,'Enlace de recuperación consumido no puede reutilizarse',$r->getStatusCode());
    $users['b']->update(['estado'=>'inactivo']);
    $r=$apiB->get('/api/v1/gastos-comunes');
    record('API-13',$r->getStatusCode()===403,'Token de cuenta inactiva se rechaza sin error de servidor',$r->getStatusCode());
} finally {
    foreach ($users as $user) { $user->departamentos()->detach(); $user->tokens()->delete(); DB::table('password_reset_tokens')->where('email',$user->email)->delete(); }
    foreach ($departamentos as $d) { Encomienda::where('departamento_id',$d->id)->delete(); $d->contactosEmergencia()->delete(); $d->gastosComunes()->delete(); $d->delete(); }
    foreach ($users as $user) $user->delete();
    foreach (array_diff(Storage::disk('public')->files('comprobantes'),$beforeFiles) as $path) Storage::disk('public')->delete($path);
    file_put_contents(storage_path('app/auditoria-integral.json'),json_encode($results,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
}
exit(collect($results)->contains(fn($r)=>!$r['passed'])?1:0);
