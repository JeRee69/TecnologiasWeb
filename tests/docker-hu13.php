<?php

// Integración HTTP con Nginx/MySQL y datos propios que se eliminan al terminar.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $error) {
    fwrite(STDERR, $error->getMessage()."\n");
    exit(1);
});

use App\Models\Departamento;
use App\Models\GastoComun;
use App\Models\Rol;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

function verify(bool $condition, string $message): void
{
    if (! $condition) throw new RuntimeException($message);
    echo "PASS: $message\n";
}
function browser(): Client
{
    return new Client(['base_uri' => 'http://web', 'cookies' => new CookieJar,
        'allow_redirects' => false, 'http_errors' => false, 'timeout' => 20]);
}
function token(Client $client, string $path): string
{
    $response = $client->get($path);
    if ($response->getStatusCode() !== 200 || !preg_match('/name="_token" value="([^"]+)"/', (string) $response->getBody(), $match)) {
        throw new RuntimeException("No se pudo abrir $path");
    }
    return $match[1];
}
function login(User $user): Client
{
    $client = browser();
    $response = $client->post('/login', ['form_params' => ['_token' => token($client, '/login'),
        'identifier' => $user->email, 'password' => 'PruebaHU13!123']]);
    verify($response->getStatusCode() === 302 && !str_ends_with($response->getHeaderLine('Location'), '/login'), 'Cuenta temporal autenticada');
    return $client;
}
function records(string $html): array
{
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
    $records = [];
    foreach ((new DOMXPath($document))->query('//article[@data-gasto]') as $article) {
        $records[(int) $article->getAttribute('data-gasto')] = $article->textContent;
    }
    return $records;
}

$users = [];
$departamentos = [];
$suffix = bin2hex(random_bytes(6));
$path = '/residente/gastos-comunes';
try {
    foreach (['a' => 'Residente', 'b' => 'Residente', 'sin' => 'Residente', 'admin' => 'Administrador', 'conserje' => 'Conserje'] as $key => $role) {
        $users[$key] = User::create(['nombre' => "HU13 $key", 'email' => "HU13-$key-$suffix@example.test",
            'password' => 'PruebaHU13!123', 'rol_id' => Rol::where('nombre', $role)->value('id'), 'estado' => 'activo']);
    }
    foreach (['a', 'b', 'inactivo', 'vacio'] as $key) {
        $departamentos[$key] = Departamento::create(['numero' => "HU13-$key-$suffix<script>", 'piso' => 1,
            'estado' => $key === 'inactivo' ? 'inactivo' : 'activo']);
    }
    $users['a']->departamentos()->attach([$departamentos['a']->id => ['es_titular' => false], $departamentos['inactivo']->id => ['es_titular' => false]]);
    $users['b']->departamentos()->attach($departamentos['b']->id, ['es_titular' => true]);
    $owned = [];
    $foreign = [];
    for ($i = 1; $i <= 12; $i++) {
        foreach (['a', 'b'] as $key) {
            $gasto = $departamentos[$key]->gastosComunes()->create(['periodo' => sprintf('2025-%02d-01', $i),
                'monto' => 80000 + $i, 'estado' => $i % 2 ? 'pendiente' : 'pagado']);
            if ($i !== 1) {
                $gasto->detalles()->createMany([
                    ['concepto' => "Concepto-$key-$i-$suffix<script>", 'monto' => 70000],
                    ['concepto' => 'Fondo de reserva', 'monto' => 10000 + $i],
                ]);
            }
            if ($key === 'a') $owned[] = $gasto;
            else $foreign[] = $gasto;
        }
    }
    $inactive = $departamentos['inactivo']->gastosComunes()->create(['periodo' => '2094-01-01', 'monto' => 90000, 'estado' => 'pendiente']);
    $owned[] = $inactive;
    $resident = login($users['a']);
    verify(str_contains((string) $resident->get('/residente/dashboard')->getBody(), $path), 'Portal enlaza a mis gastos comunes');
    $expected = collect($owned)->sortByDesc(fn ($g) => $g->periodo->format('Y-m-d'))->values();
    $all = [];
    foreach ([1, 2] as $page) {
        $response = $resident->get($path.'?page='.$page.'&departamento_id='.$departamentos['b']->id.'&user_id='.$users['b']->id.'&gasto_id='.$foreign[0]->id);
        $html = (string) $response->getBody();
        $found = records($html);
        verify($response->getStatusCode() === 200 && array_keys($found) === $expected->slice(($page - 1) * 10, 10)->pluck('id')->all(), "Página $page solo muestra cobros propios en orden mensual descendente (HTTP ".$response->getStatusCode().'; recibidos '.json_encode(array_keys($found)).'; esperados '.json_encode($expected->slice(($page - 1) * 10, 10)->pluck('id')->all()).')');
        verify(str_contains($response->getHeaderLine('Cache-Control'), 'private') && str_contains($response->getHeaderLine('Cache-Control'), 'no-store'), 'Consulta privada sin caché');
        foreach ($expected->slice(($page - 1) * 10, 10) as $gasto) {
            $text = $found[$gasto->id];
            verify(str_contains($text, $gasto->departamento->numero) && str_contains($text, $gasto->periodo->format('m/Y'))
                && str_contains($text, '$'.number_format($gasto->monto, 0, ',', '.')) && str_contains($text, ucfirst($gasto->estado)), 'Departamento, período, monto y estado coinciden con los datos persistidos');
            foreach ($gasto->detalles as $detalle) {
                verify(str_contains($text, $detalle->concepto) && str_contains($text, '$'.number_format($detalle->monto, 0, ',', '.')), 'Detalle incluye concepto y monto reales');
            }
        }
        verify(!str_contains($html, "HU13-b-$suffix") && !str_contains($html, "Concepto-b-") && !str_contains($html, '<script>'), 'No se filtran unidades ni conceptos ajenos; texto escapado');
        $all += $found;
    }
    verify(count($all) === 13 && isset($all[$inactive->id]) && str_contains($all[$owned[0]->id], 'Desglose aún no registrado'), 'Incluye historial de unidad inactiva y aviso de desglose pendiente');
    $fresh = login($users['a']);
    verify(array_keys(records((string) $fresh->get($path)->getBody())) === $expected->take(10)->pluck('id')->all(), 'Otra sesión consulta el mismo historial persistido');
    $other = login($users['b']);
    verify(array_keys(records((string) $other->get($path)->getBody())) === collect($foreign)->reverse()->take(10)->pluck('id')->values()->all(), 'Otro residente ve exclusivamente su propio departamento');
    $empty = login($users['sin']);
    verify(str_contains((string) $empty->get($path)->getBody(), 'Sin departamento asociado') && records((string) $empty->get($path)->getBody()) === [], 'Sin asociación no accede a cobros');
    $users['sin']->departamentos()->attach($departamentos['vacio']->id);
    verify(str_contains((string) $empty->get($path)->getBody(), 'Sin gastos comunes registrados'), 'Departamento sin cobros muestra estado vacío');
    verify(str_contains((string) $resident->get($path.'?page=999')->getBody(), 'Volver a la primera página'), 'Página fuera de rango permite regresar');
    // Perder la asociación revoca el acceso a los cobros desde la siguiente consulta.
    $users['a']->departamentos()->detach($departamentos['a']->id);
    verify(array_keys(records((string) $resident->get($path.'?departamento_id='.$departamentos['a']->id)->getBody())) === [$inactive->id], 'Desasociación impide recuperar el historial con parámetros manipulados');
    // Habitantes del mismo departamento pueden consultar sus cobros sin ser titulares.
    $users['b']->departamentos()->attach($departamentos['inactivo']->id, ['es_titular' => false]);
    verify(isset(records((string) $other->get($path)->getBody())[$inactive->id]), 'Residentes vinculados a una unidad compartida ven sus cobros');
    foreach (['admin', 'conserje'] as $key) {
        verify(login($users[$key])->get($path)->getStatusCode() === 403, "$key no accede al portal financiero del residente");
    }
    verify(browser()->get($path)->getStatusCode() === 302, 'Invitado debe iniciar sesión');
    $users['a']->update(['estado' => 'inactivo']);
    verify($resident->get($path)->getStatusCode() === 302, 'Residente inactivo pierde acceso');
    $admin = login($users['admin']);
    $csrf = token($other, '/perfil');
    verify($other->put('/admin/gastos-comunes/'.$inactive->id.'/desglose', ['form_params' => ['_token' => $csrf, 'detalles' => [['concepto' => 'Cambio', 'monto' => 90000]]]])->getStatusCode() === 403
        && $inactive->detalles()->count() === 0, 'Consulta no otorga permisos para modificar desgloses');
    // Un desglose registrado por administración se refleja en la consulta del residente.
    $desglose = '/admin/gastos-comunes/'.$inactive->id.'/desglose';
    verify($admin->put($desglose, ['form_params' => ['_token' => token($admin, $desglose),
        'detalles' => [['concepto' => 'Mantención visible HU13', 'monto' => 90000]]]])->getStatusCode() === 302
        && str_contains((string) $other->get($path)->getBody(), 'Mantención visible HU13'), 'Desglose administrativo actualizado visible para el residente asociado');
    echo "HU-13 verificada.\n";
} finally {
    foreach ($users as $user) $user->departamentos()->detach();
    foreach ($departamentos as $departamento) {
        $departamento->gastosComunes()->delete();
        $departamento->delete();
    }
    foreach ($users as $user) $user->delete();
}
