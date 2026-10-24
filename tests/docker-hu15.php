<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $error) { fwrite(STDERR, $error->getMessage()."\n"); exit(1); });

use App\Models\Departamento;
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
    return new Client(['base_uri' => 'http://web', 'cookies' => new CookieJar, 'allow_redirects' => false, 'http_errors' => false, 'timeout' => 30]);
}
function token(Client $client, string $path): string
{
    $response = $client->get($path);
    if ($response->getStatusCode() !== 200 || !preg_match('/name="_token" value="([^"]+)"/', (string) $response->getBody(), $match)) throw new RuntimeException("No se pudo abrir $path");
    return $match[1];
}
function login(User $user): Client
{
    $client = browser();
    $response = $client->post('/login', ['form_params' => ['_token' => token($client, '/login'), 'identifier' => $user->email, 'password' => 'PruebaHU15!123']]);
    verify($response->getStatusCode() === 302 && !str_ends_with($response->getHeaderLine('Location'), '/login'), 'Login temporal');
    return $client;
}
function download(Client $client, string $url, string $name, string $output): void
{
    $response = $client->get($url);
    $bytes = (string) $response->getBody();
    verify($response->getStatusCode() === 200 && $response->getHeaderLine('Content-Type') === 'application/pdf' && str_starts_with($bytes, '%PDF-') && str_contains($bytes, '%%EOF'), "PDF válido: $name");
    verify(str_contains($response->getHeaderLine('Content-Disposition'), 'attachment; filename="gasto-comun-') && str_contains($response->getHeaderLine('Cache-Control'), 'private') && str_contains($response->getHeaderLine('Cache-Control'), 'no-store') && $response->getHeaderLine('X-Content-Type-Options') === 'nosniff', 'Descarga privada con nombre seguro');
    file_put_contents($output.'/'.$name.'.pdf', $bytes);
}

$users = [];
$departamentos = [];
$suffix = bin2hex(random_bytes(6));
$output = storage_path('app/hu15-qa');
if (!is_dir($output)) mkdir($output, 0775, true);
try {
    foreach (['r' => 'Residente', 'otro' => 'Residente', 'admin' => 'Administrador', 'conserje' => 'Conserje', 'sin' => 'Residente'] as $key => $role) {
        $users[$key] = User::create(['nombre' => "HU15 $key", 'email' => "hu15-$key-$suffix@example.test", 'password' => 'PruebaHU15!123', 'estado' => 'activo', 'rol_id' => Rol::where('nombre', $role)->value('id')]);
    }
    foreach (['r', 'otro'] as $key) {
        $departamentos[$key] = Departamento::create(['numero' => $key === 'r' ? 'PDF-'.$suffix.'-Á<101>' : 'PRIVADO-'.$suffix, 'piso' => 1, 'estado' => 'activo']);
        $users[$key]->departamentos()->attach($departamentos[$key]->id, ['es_titular' => false]);
    }
    $gasto = $departamentos['r']->gastosComunes()->create(['periodo' => '2096-03-01', 'monto' => 85000, 'estado' => 'pendiente']);
    $gasto->detalles()->createMany([
        ['concepto' => 'Fondo de reserva', 'monto' => 15000],
        ['concepto' => 'Mantención de ascensores', 'monto' => 30000],
        ['concepto' => 'Consumos básicos <script>alert(1)</script>', 'monto' => 40000],
    ]);
    $ajeno = $departamentos['otro']->gastosComunes()->create(['periodo' => '2096-03-01', 'monto' => 99999, 'estado' => 'pendiente']);
    $url = '/residente/gastos-comunes/'.$gasto->id.'/pdf';
    $resident = login($users['r']);
    verify(str_contains((string) $resident->get('/residente/gastos-comunes')->getBody(), $url), 'Listado enlaza a la descarga del cobro');
    $snapshot = $gasto->fresh()->getAttributes();
    download($resident, $url.'?user_id='.$users['otro']->id.'&departamento_id='.$departamentos['otro']->id, 'pendiente', $output);
    verify($gasto->fresh()->getAttributes() === $snapshot, 'Descargar no cambia los datos del cobro');
    foreach ([$ajeno->id, 999999999] as $id) verify($resident->get('/residente/gastos-comunes/'.$id.'/pdf')->getStatusCode() === 404, 'Cobro ajeno o inexistente devuelve 404');
    foreach (['otro', 'sin'] as $key) verify(login($users[$key])->get($url)->getStatusCode() === 404, "$key no descarga cobros sin asociación");
    foreach (['admin', 'conserje'] as $key) verify(login($users[$key])->get($url)->getStatusCode() === 403, "$key no accede a la descarga del residente");
    verify(browser()->get($url)->getStatusCode() === 302, 'Invitado debe autenticarse');
    $departamentos['r']->update(['estado' => 'inactivo']);
    download($resident, $url, 'unidad-inactiva', $output);
    $admin = login($users['admin']);
    $estadoUrl = '/admin/gastos-comunes/'.$gasto->id.'/estado';
    $csrf = token($admin, $estadoUrl);
    verify($admin->patch('/admin/gastos-comunes/'.$gasto->id.'/vencimiento', ['form_params' => ['_token' => $csrf, 'fecha_vencimiento' => today('America/Santiago')->subDay()->toDateString()]])->getStatusCode() === 302, 'Administrador configura vencimiento');
    download($resident, $url, 'moroso', $output);
    verify($admin->post('/admin/gastos-comunes/'.$gasto->id.'/validar-pago', ['form_params' => ['_token' => $csrf, 'monto_recibido' => 85000, 'referencia_pago' => 'REFERENCIA-INTERNA-HU15']])->getStatusCode() === 302, 'Administrador valida pago');
    download($resident, $url, 'pagado', $output);
    $sin = $departamentos['r']->gastosComunes()->create(['periodo' => '2096-04-01', 'monto' => 1000, 'estado' => 'pendiente']);
    download($resident, '/residente/gastos-comunes/'.$sin->id.'/pdf', 'sin-desglose', $output);
    $largo = $departamentos['r']->gastosComunes()->create(['periodo' => '2096-05-01', 'monto' => 50000, 'estado' => 'pendiente']);
    for ($i = 1; $i <= 50; $i++) $largo->detalles()->create(['concepto' => sprintf('Concepto %02d: ', $i).str_repeat('Mantención eléctrica y consumo básico. ', 4), 'monto' => 1000]);
    download($resident, '/residente/gastos-comunes/'.$largo->id.'/pdf', 'desglose-largo', $output);
    $users['r']->departamentos()->detach();
    verify($resident->get($url)->getStatusCode() === 404, 'Desasociar al residente revoca también la descarga');
    $users['r']->departamentos()->attach($departamentos['r']->id);
    $users['r']->update(['estado' => 'inactivo']);
    verify($resident->get($url)->getStatusCode() === 302, 'Cuenta inactiva pierde acceso al PDF');
    echo "HU-15 HTTP verificada. PDFs de revisión: $output\n";
} finally {
    foreach ($users as $user) $user->departamentos()->detach();
    foreach ($departamentos as $departamento) { $departamento->gastosComunes()->delete(); $departamento->delete(); }
    foreach ($users as $user) $user->delete();
}
