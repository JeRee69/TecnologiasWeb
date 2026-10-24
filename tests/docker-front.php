<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $error) { fwrite(STDERR, $error->getMessage()."\n"); exit(1); });

use App\Models\Departamento;
use App\Models\EspacioComun;
use App\Models\Reserva;
use App\Models\Encomienda;
use App\Models\Rol;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

function verify(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
    echo "PASS: $message\n";
}
function browser(): Client
{
    return new Client(['base_uri'=>'http://web','cookies'=>new CookieJar,'allow_redirects'=>false,'http_errors'=>false,'timeout'=>20]);
}
function login(User $user): Client
{
    $client = browser();
    preg_match('/name="_token" value="([^"]+)"/', (string) $client->get('/login')->getBody(), $token);
    $response = $client->post('/login', ['form_params'=>['_token'=>$token[1],'identifier'=>$user->email,'password'=>'FrontTest!123456']]);
    verify($response->getStatusCode() === 302, 'Login con formulario real');
    return $client;
}
function page(Client $client, string $url, string $zone): string
{
    $response = $client->get($url);
    $html = (string) $response->getBody();
    verify($response->getStatusCode() === 200, "Vista accesible: $url");
    verify(substr_count($html, 'id="app-sidebar"') === 1 && substr_count($html, 'id="contenido"') === 1 && str_contains($html, '/css/ediconnect.css'), 'Estructura compartida única y estilos cargados');
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
    $xpath = new DOMXPath($document);
    $menuCorrecto = true;
    foreach ($xpath->query('//aside[@id="app-sidebar"]//a[@href]') as $link) {
        $path = parse_url($link->getAttribute('href'), PHP_URL_PATH);
        $menuCorrecto = $menuCorrecto && (str_starts_with($path, '/'.$zone.'/') || $path === '/perfil');
    }
    verify($menuCorrecto, 'Navegación limitada al rol de la sesión');
    verify($xpath->query('//aside//a[@aria-current="page"]')->length === 1, 'Se identifica una sola sección activa');
    return $html;
}

$users=[]; $departamentos=[]; $espacio=null;
$suffix=bin2hex(random_bytes(5));
try {
    foreach (['admin'=>'Administrador','conserje'=>'Conserje','a'=>'Residente','b'=>'Residente'] as $key=>$role) {
        $users[$key]=User::create(['nombre'=>"Front $key",'email'=>"front-$key-$suffix@example.test",'password'=>'FrontTest!123456','rol_id'=>Rol::where('nombre',$role)->value('id'),'estado'=>'activo']);
    }
    $espacio=EspacioComun::create(['nombre'=>"Espacio Front $suffix",'descripcion'=>'Espacio temporal','estado'=>'activo']);
    foreach (['a','b'] as $key) {
        $departamentos[$key]=Departamento::create(['numero'=>"FRONT-$key-$suffix",'piso'=>1,'estado'=>'activo']);
        $users[$key]->departamentos()->attach($departamentos[$key]->id);
        $gasto=$departamentos[$key]->gastosComunes()->create(['periodo'=>'2097-01-01','monto'=>12345,'estado'=>'pendiente']);
        Encomienda::create(['departamento_id'=>$departamentos[$key]->id,'conserje_id'=>$users['conserje']->id,'empresa_delivery'=>"EMPRESA-$key-$suffix",'fecha_ingreso'=>now(),'estado'=>'Ingresado']);
        Reserva::create(['espacio_comun_id'=>$espacio->id,'user_id'=>$users[$key]->id,'fecha'=>today()->addDays(5),'hora_inicio'=>'10:00','hora_fin'=>'11:00','estado'=>'pendiente']);
    }
    $admin=login($users['admin']);
    $conserje=login($users['conserje']);
    $resident=login($users['a']);
    foreach (['/admin/dashboard','/admin/departamentos','/admin/usuarios','/admin/gastos-comunes','/admin/espacios-comunes','/admin/reservas','/perfil',
        '/admin/departamentos/'.$departamentos['a']->id,'/admin/espacios-comunes/'.$espacio->id.'/configuracion'] as $url) page($admin,$url,'admin');
    $gasto=$departamentos['a']->gastosComunes()->first();
    foreach (['/admin/gastos-comunes/'.$gasto->id.'/desglose','/admin/gastos-comunes/'.$gasto->id.'/estado'] as $url) page($admin,$url,'admin');
    foreach (['/conserje/dashboard','/conserje/encomiendas','/conserje/visitas','/conserje/vehiculos','/perfil'] as $url) page($conserje,$url,'conserje');
    foreach (['/residente/dashboard','/residente/gastos-comunes','/residente/encomiendas','/residente/reservas','/residente/espacios-comunes/disponibilidad','/perfil'] as $url) page($resident,$url,'residente');
    $html=page($resident,'/residente/dashboard','residente');
    verify(str_contains($html,"EMPRESA-a-$suffix") && str_contains($html,"FRONT-a-$suffix") && !str_contains($html,"EMPRESA-b-$suffix") && !str_contains($html,"FRONT-b-$suffix"), 'Panel del residente muestra solo sus unidades y encomiendas');
    verify(str_contains($html,'$12.345') && str_contains($html,"Espacio Front $suffix"), 'Panel muestra monto y próxima reserva persistidos');
    foreach (['/css/ediconnect.css','/js/ediconnect.js'] as $url) verify($admin->get($url)->getStatusCode()===200,'Recurso local disponible');
    foreach (['/login','/recuperar-contrasena'] as $url) verify(browser()->get($url)->getStatusCode()===200,'Vista pública de autenticación accesible');
    verify(browser()->get('/restablecer-contrasena/token-de-prueba')->getStatusCode()===302, 'Enlace de recuperación inválido conserva su validación');
    verify($resident->get('/admin/departamentos')->getStatusCode()===403,'Ocultar módulos no sustituye la protección del servidor');
    echo "Frontend por roles verificado.\n";
} finally {
    foreach ($users as $user) { $user->departamentos()->detach(); Reserva::where('user_id',$user->id)->delete(); }
    foreach ($departamentos as $departamento) { Encomienda::where('departamento_id',$departamento->id)->delete(); $departamento->gastosComunes()->delete(); $departamento->delete(); }
    if ($espacio) $espacio->delete();
    foreach ($users as $user) $user->delete();
}
