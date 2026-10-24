<?php

// Ejecutar dentro de Docker. Solo crea y elimina datos temporales propios.
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

function check(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
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
    check($response->getStatusCode() === 200, "Página $path accesible");
    preg_match('/name="_token" value="([^"]+)"/', (string) $response->getBody(), $match);
    return $match[1] ?? throw new RuntimeException('Falta token CSRF');
}
function login(User $user): Client
{
    $client = browser();
    $response = $client->post('/login', ['form_params' => ['_token' => token($client, '/login'),
        'identifier' => $user->email, 'password' => 'PruebaHU11!123']]);
    check($response->getStatusCode() === 302 && ! str_ends_with($response->getHeaderLine('Location'), '/login'), 'Login de prueba');
    return $client;
}

$suffix = bin2hex(random_bytes(5));
$users = [];
$departamentos = [];
$path = '/admin/gastos-comunes';
try {
    foreach (['Administrador', 'Residente', 'Conserje'] as $role) {
        $users[$role] = User::create(['nombre' => "HU11 $role", 'email' => "$role-$suffix@example.test",
            'password' => password_hash('PruebaHU11!123', PASSWORD_BCRYPT),
            'rol_id' => Rol::where('nombre', $role)->value('id'), 'estado' => 'activo']);
    }
    foreach (['activo', 'activo', 'inactivo'] as $i => $state) {
        $departamentos[] = Departamento::create(['numero' => "HU11-$suffix-$i<script>", 'piso' => 1, 'estado' => $state]);
    }
    $admin = login($users['Administrador']);
    $csrf = token($admin, $path);
    $base = ['_token' => $csrf, 'departamento_id' => $departamentos[0]->id, 'periodo' => '2091-04', 'monto' => '85000'];
    $post = fn (array $changes) => $admin->post($path, ['headers' => ['Accept' => 'application/json'], 'form_params' => array_replace($base, $changes)]);
    $query = fn () => GastoComun::whereIn('departamento_id', array_map(fn ($d) => $d->id, $departamentos));
    check(str_contains((string) $admin->get('/admin/dashboard')->getBody(), $path), 'Panel enlaza al módulo');
    check($post(['estado' => 'pagado', 'id' => 999999, 'created_at' => '2000-01-01'])->getStatusCode() === 302, 'Administrador genera cobro');
    $gasto = $query()->firstOrFail();
    check($gasto->monto === 85000 && $gasto->estado === 'pendiente' && $gasto->periodo->format('Y-m-d') === '2091-04-01'
        && $gasto->departamento_id === $departamentos[0]->id && $gasto->id !== 999999, 'Persisten departamento, período, monto y estado inicial seguro');
    check($post(['monto' => 999])->getStatusCode() === 422 && $gasto->fresh()->monto === 85000 && $query()->count() === 1, 'Duplicado rechazado sin sobrescribir');
    foreach ([['monto' => ''], ['monto' => 0], ['monto' => -1], ['monto' => '1.5'], ['monto' => 'texto'],
        ['monto' => ['x']], ['monto' => '1000000000000'], ['periodo' => '2026-13'], ['periodo' => '2026-01-01'],
        ['periodo' => '1899-12'], ['periodo' => ['x']], ['departamento_id' => 999999999],
        ['departamento_id' => $departamentos[2]->id]] as $invalid) {
        check($post($invalid)->getStatusCode() === 422 && $query()->count() === 1, 'Datos inválidos rechazados sin crear cobros');
    }
    $admin->post($path, ['headers' => ['Referer' => 'http://web'.$path],
        'form_params' => array_replace($base, ['monto' => ['x'], 'periodo' => ['x'], 'departamento_id' => ['x']])]);
    check($admin->get($path)->getStatusCode() === 200, 'Formulario tolera valores anteriores malformados');
    check($post(['periodo' => '2091-05', 'monto' => '999999999999'])->getStatusCode() === 302, 'Otro mes y monto máximo aceptados');
    check($post(['departamento_id' => $departamentos[1]->id])->getStatusCode() === 302, 'Otro departamento admite el mismo período');
    $fresh = login($users['Administrador']);
    $html = (string) $fresh->get($path.'?periodo=2091-04')->getBody();
    check(str_contains($html, '04/2091') && ! str_contains($html, '05/2091') && str_contains($html, '$85.000')
        && str_contains($html, 'Pendiente') && str_contains($html, '&lt;script&gt;'), 'Listado persistido muestra columnas, filtra el mes y escapa texto');
    check($admin->post($path, ['form_params' => array_diff_key($base, ['_token' => true])])->getStatusCode() === 419, 'Generación requiere CSRF');
    // Dos sesiones simultáneas no pueden generar dos cobros para la misma unidad y mes.
    $csrf2 = token($fresh, $path);
    $concurrent = array_replace($base, ['periodo' => '2091-06']);
    $responses = Utils::unwrap([
        $admin->postAsync($path, ['headers' => ['Accept' => 'application/json'], 'form_params' => $concurrent]),
        $fresh->postAsync($path, ['headers' => ['Accept' => 'application/json'], 'form_params' => array_replace($concurrent, ['_token' => $csrf2])]),
    ]);
    $codes = array_map(fn ($r) => $r->getStatusCode(), $responses);
    sort($codes);
    check($codes === [302, 422] && $query()->where('periodo', '2091-06-01')->count() === 1, 'Generación simultánea crea exactamente un cobro');
    $count = $query()->count();
    foreach (['Residente', 'Conserje'] as $role) {
        $client = login($users[$role]);
        check($client->get($path)->getStatusCode() === 403, "$role no accede al listado");
        check($client->post($path, ['form_params' => array_replace($base, ['_token' => token($client, '/perfil')])])->getStatusCode() === 403, "$role no genera cobros");
    }
    check(browser()->get($path)->getStatusCode() === 302, 'Invitado requiere login');
    $users['Administrador']->update(['estado' => 'inactivo']);
    check($post(['periodo' => '2091-07'])->getStatusCode() === 403 && $admin->get($path)->getStatusCode() === 302
        && $query()->count() === $count, 'Administrador inactivo pierde acceso y no genera cobros');
    echo "HU-11 verificada.\n";
} finally {
    foreach ($departamentos as $departamento) {
        $departamento->gastosComunes()->delete();
        $departamento->delete();
    }
    foreach ($users as $user) {
        $user->delete();
    }
}
