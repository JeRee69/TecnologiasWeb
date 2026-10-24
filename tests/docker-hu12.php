<?php

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
use GuzzleHttp\Promise\Utils;

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
        'identifier' => $user->email, 'password' => 'PruebaHU12!123']]);
    verify($response->getStatusCode() === 302 && !str_ends_with($response->getHeaderLine('Location'), '/login'), 'Login de prueba');
    return $client;
}

$users = [];
$departamento = null;
$suffix = bin2hex(random_bytes(6));
try {
    foreach (['Administrador', 'Residente', 'Conserje'] as $role) {
        $users[$role] = User::create(['nombre' => "HU12 $role", 'email' => "$role-$suffix@example.test",
            'password' => 'PruebaHU12!123', 'rol_id' => Rol::where('nombre', $role)->value('id'), 'estado' => 'activo']);
    }
    $departamento = Departamento::create(['numero' => "HU12-$suffix", 'piso' => 1, 'estado' => 'activo']);
    $gasto = $departamento->gastosComunes()->create(['periodo' => '2092-01-01', 'monto' => 85000, 'estado' => 'pendiente']);
    $otro = $departamento->gastosComunes()->create(['periodo' => '2092-02-01', 'monto' => 85000, 'estado' => 'pendiente']);
    $original = $gasto->fresh()->getAttributes();
    $path = "/admin/gastos-comunes/{$gasto->id}/desglose";
    $admin = login($users['Administrador']);
    $csrf = token($admin, $path);
    verify(str_contains((string) $admin->get($path)->getBody(), 'aún no tiene desglose') && $gasto->detalles()->count() === 0, 'Cobro previo conserva monto y no inventa conceptos');
    verify(str_contains((string) $admin->get('/admin/gastos-comunes?periodo=2092-01')->getBody(), $path), 'Listado enlaza al desglose');
    $detalles = [
        ['concepto' => 'Fondo de reserva', 'monto' => 15000],
        ['concepto' => 'Mantenciones', 'monto' => 30000],
        ['concepto' => 'Consumos básicos', 'monto' => 40000],
    ];
    $save = fn (array $payload) => $admin->put($path, ['headers' => ['Accept' => 'application/json'],
        'form_params' => array_replace(['_token' => $csrf], $payload)]);
    verify($save(['detalles' => $detalles, 'monto' => 1, 'estado' => 'pagado', 'departamento_id' => 999999])->getStatusCode() === 302, 'Administrador registra conceptos que suman el monto asignado');
    $snapshot = fn () => $gasto->detalles()->get()->toArray();
    $saved = $snapshot();
    verify(array_column($saved, 'concepto') === array_column($detalles, 'concepto') && array_sum(array_column($saved, 'monto')) === 85000
        && $gasto->fresh()->getAttributes() === $original && $otro->detalles()->count() === 0, 'Persisten conceptos, sin cambiar el cobro ni otro período');
    foreach ([
        [], ['detalles' => []], ['detalles' => 'texto'], ['detalles' => ['texto']],
        ['detalles' => [['concepto' => '', 'monto' => 85000]]],
        ['detalles' => [['concepto' => '   ', 'monto' => 85000]]],
        ['detalles' => [['concepto' => ['x'], 'monto' => 85000]]],
        ['detalles' => [['concepto' => str_repeat('x', 201), 'monto' => 85000]]],
        ['detalles' => [['concepto' => 'X', 'monto' => 0]]],
        ['detalles' => [['concepto' => 'X', 'monto' => -1]]],
        ['detalles' => [['concepto' => 'X', 'monto' => '85000.5']]],
        ['detalles' => [['concepto' => 'X', 'monto' => ['x']]]],
        ['detalles' => [['concepto' => 'X', 'monto' => '1000000000000']]],
        ['detalles' => [['concepto' => 'X', 'monto' => 84999]], 'total' => 85000],
        ['detalles' => [['concepto' => 'X', 'monto' => 85001]], 'monto' => 85001],
        ['detalles' => array_fill(0, 51, ['concepto' => 'X', 'monto' => 1])],
        ['detalles' => [5 => ['concepto' => 'X', 'monto' => 85000]]],
    ] as $invalid) {
        verify($save($invalid)->getStatusCode() === 422 && $snapshot() === $saved, 'Desglose inválido se rechaza y conserva todos los conceptos anteriores');
    }
    $admin->put($path, ['headers' => ['Referer' => 'http://web'.$path], 'form_params' => ['_token' => $csrf,
        'detalles' => [['concepto' => 'Valor conservado', 'monto' => 123]]]]);
    $html = (string) $admin->get($path)->getBody();
    verify(str_contains($html, 'Valor conservado') && str_contains($html, 'value="123"') && str_contains($html, 'debe coincidir'), 'Error conserva entradas y explica la diferencia');
    $admin->put($path, ['headers' => ['Referer' => 'http://web'.$path], 'form_params' => ['_token' => $csrf,
        'detalles' => [['concepto' => ['x'], 'monto' => ['x']]]]]);
    verify($admin->get($path)->getStatusCode() === 200, 'Formulario admite entradas malformadas sin error de servidor');
    $nuevo = [['concepto' => '<script>alert(1)</script>', 'monto' => 35000], ['concepto' => str_repeat('a', 200), 'monto' => 50000]];
    verify($save(['detalles' => $nuevo])->getStatusCode() === 302 && $gasto->detalles()->count() === 2, 'Edición reemplaza el desglose y admite conceptos personalizados');
    $fresh = login($users['Administrador']);
    $response = $fresh->get($path);
    $html = (string) $response->getBody();
    verify(str_contains($html, '&lt;script&gt;alert(1)&lt;/script&gt;') && !str_contains($html, '<script>alert(1)</script>')
        && str_contains($html, 'value="35000"') && str_contains($response->getHeaderLine('Cache-Control'), 'no-store'), 'Desglose persistido visible en otra sesión con texto seguro y sin caché');
    // Reemplazos simultáneos se guardan completos, sin mezclar las filas.
    $a = [['concepto' => 'A1', 'monto' => 10000], ['concepto' => 'A2', 'monto' => 75000]];
    $b = [['concepto' => 'B1', 'monto' => 20000], ['concepto' => 'B2', 'monto' => 25000], ['concepto' => 'B3', 'monto' => 40000]];
    $responses = Utils::unwrap([
        $admin->putAsync($path, ['form_params' => ['_token' => $csrf, 'detalles' => $a]]),
        $fresh->putAsync($path, ['form_params' => ['_token' => token($fresh, $path), 'detalles' => $b]]),
    ]);
    $final = $gasto->detalles()->get(['concepto', 'monto'])->toArray();
    verify($responses[0]->getStatusCode() === 302 && $responses[1]->getStatusCode() === 302 && ($final === $a || $final === $b), 'Ediciones simultáneas conservan un desglose completo y consistente');
    $saved = $snapshot();
    verify($admin->put($path, ['form_params' => ['detalles' => $a]])->getStatusCode() === 419 && $snapshot() === $saved, 'Guardar exige CSRF');
    foreach (['Residente', 'Conserje'] as $role) {
        $client = login($users[$role]);
        verify($client->get($path)->getStatusCode() === 403 && $client->put($path, ['form_params' => ['_token' => token($client, '/perfil'), 'detalles' => $a]])->getStatusCode() === 403
            && $snapshot() === $saved, "$role no consulta ni modifica el desglose");
    }
    $guest = browser();
    verify($guest->get($path)->getStatusCode() === 302 && $guest->put($path, ['form_params' => ['_token' => token($guest, '/login'), 'detalles' => $a]])->getStatusCode() === 302, 'Invitado requiere autenticación');
    verify($admin->get('/admin/gastos-comunes/999999999/desglose')->getStatusCode() === 404, 'Cobro inexistente devuelve 404');
    $users['Administrador']->update(['estado' => 'inactivo']);
    verify($save(['detalles' => $a])->getStatusCode() === 403 && $fresh->get($path)->getStatusCode() === 302 && $snapshot() === $saved, 'Cuenta inactiva no consulta ni guarda desgloses');
    echo "HU-12 verificada.\n";
} finally {
    if ($departamento) {
        $departamento->gastosComunes()->delete();
        $departamento->delete();
    }
    foreach ($users as $user) $user->delete();
}
