<?php

// Integración HU-19: cuentas y espacios temporales, con limpieza al terminar.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use App\Models\EspacioComun;

function checkSpace(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
    echo "PASS: $message\n";
}

function spaceBrowser(): Client
{
    return new Client(['base_uri' => 'http://web', 'cookies' => new CookieJar,
        'allow_redirects' => false, 'http_errors' => false, 'timeout' => 15]);
}

function spaceToken(Client $client, string $path): string
{
    $response = $client->get($path);
    if ($response->getStatusCode() !== 200 ||
        ! preg_match('/name="_token" value="([^"]+)"/', (string) $response->getBody(), $matches)) {
        throw new RuntimeException("No se pudo abrir $path");
    }
    return $matches[1];
}

function spaceLogin(string $email): Client
{
    $client = spaceBrowser();
    $response = $client->post('/login', ['form_params' => [
        '_token' => spaceToken($client, '/login'), 'identifier' => $email, 'password' => 'PruebaHU19!123',
    ]]);
    checkSpace($response->getStatusCode() === 302 && ! str_ends_with($response->getHeaderLine('Location'), '/login'), 'Usuario de prueba autenticado');
    return $client;
}

$db = new PDO('mysql:host='.getenv('DB_HOST').';dbname='.getenv('DB_DATABASE'),
    getenv('DB_USERNAME'), getenv('DB_PASSWORD'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$roles = $db->query('SELECT nombre, id FROM roles')->fetchAll(PDO::FETCH_KEY_PAIR);
$created = [];
$accounts = [];
$suffix = bin2hex(random_bytes(6));
$prefix = "HU19 $suffix ";
$path = '/admin/espacios-comunes';
$count = fn () => (int) $db->query('SELECT COUNT(*) FROM espacios_comunes')->fetchColumn();

try {
    foreach ($roles as $role => $roleId) {
        $email = "espacios-$roleId-$suffix@example.test";
        $db->prepare('INSERT INTO users (nombre, email, password, rol_id, estado, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())')
            ->execute(["HU19 $role", $email, password_hash('PruebaHU19!123', PASSWORD_BCRYPT), $roleId, 'activo']);
        $created[] = $id = (int) $db->lastInsertId();
        $accounts[$role] = ['id' => $id, 'email' => $email];
    }
    $admin = spaceLogin($accounts['Administrador']['email']);
    $token = spaceToken($admin, $path);
    checkSpace(str_contains((string) $admin->get('/admin/dashboard')->getBody(), 'Administrar espacios'), 'Panel enlaza al registro de espacios');
    $ids = [];
    foreach (['activo', 'inactivo'] as $state) {
        $name = $prefix.$state;
        $description = "Quincho de prueba.\n<script>alert(1)</script>";
        $response = $admin->post($path, ['form_params' => [
            '_token' => $token, 'nombre' => "  $name  ", 'descripcion' => $description,
            'estado' => $state, 'id' => 2147483647, 'created_at' => '2000-01-01',
        ]]);
        checkSpace($response->getStatusCode() === 302 && str_ends_with($response->getHeaderLine('Location'), $path), "Administrador registra un espacio $state");
        $query = $db->prepare('SELECT * FROM espacios_comunes WHERE nombre = ?');
        $query->execute([$name]);
        $record = $query->fetch(PDO::FETCH_ASSOC);
        checkSpace($record && $record['descripcion'] === $description && $record['estado'] === $state, "Nombre, descripción y estado $state persisten");
        checkSpace((int) $record['id'] !== 2147483647 && ! str_starts_with($record['created_at'], '2000'), 'Campos ajenos al registro no se asignan desde la petición');
        $ids[$state] = (int) $record['id'];
    }
    $activeIds = EspacioComun::whereIn('id', array_values($ids))->activos()->pluck('id')->all();
    checkSpace($activeIds === [$ids['activo']], 'Consulta de espacios activos excluye los inactivos');
    $fresh = spaceLogin($accounts['Administrador']['email']);
    $html = (string) $fresh->get($path)->getBody();
    checkSpace(str_contains($html, $prefix.'activo') && str_contains($html, $prefix.'inactivo'), 'Espacios visibles desde una sesión nueva');
    checkSpace(str_contains($html, '&lt;script&gt;alert(1)&lt;/script&gt;') && ! str_contains($html, '<script>alert(1)</script>'), 'Descripción se muestra como texto seguro');

    $base = ['nombre' => $prefix.'inválido', 'descripcion' => 'Descripción de prueba', 'estado' => 'activo'];
    foreach ([
        ['nombre' => ''], ['nombre' => '   '], ['nombre' => str_repeat('a', 151)], ['nombre' => ['arreglo']],
        ['descripcion' => ''], ['descripcion' => str_repeat('a', 2001)], ['descripcion' => ['arreglo']],
        ['estado' => ''], ['estado' => 'reservado'], ['estado' => ['activo']], ['nombre' => $prefix.'activo'],
    ] as $invalid) {
        $before = $count();
        $response = $admin->post($path, ['headers' => ['Accept' => 'application/json'],
            'form_params' => array_merge(['_token' => $token], $base, $invalid)]);
        checkSpace($response->getStatusCode() === 422 && $count() === $before, 'Dato inválido o duplicado rechazado sin insertar registros');
    }
    $before = $count();
    $admin->post($path, ['headers' => ['Referer' => 'http://web'.$path],
        'form_params' => ['_token' => $token, 'nombre' => ['arreglo'], 'descripcion' => ['arreglo'], 'estado' => 'activo']]);
    checkSpace($admin->get($path)->getStatusCode() === 200 && $count() === $before, 'Entradas malformadas muestran el formulario sin errores del servidor');
    $admin->post($path, ['headers' => ['Referer' => 'http://web'.$path],
        'form_params' => ['_token' => $token, 'nombre' => $prefix.'con error', 'descripcion' => '', 'estado' => 'inactivo']]);
    $html = (string) $admin->get($path)->getBody();
    checkSpace(str_contains($html, 'Describe el espacio común.') && str_contains($html, 'value="'.$prefix.'con error"') &&
        preg_match('/value="inactivo"\s+selected/', $html) === 1 && $count() === $before, 'Error visible conserva nombre y estado ingresados');
    checkSpace($admin->post($path, ['form_params' => $base])->getStatusCode() === 419 && $count() === $before, 'Registro requiere CSRF');

    foreach (['Conserje', 'Residente'] as $role) {
        $client = spaceLogin($accounts[$role]['email']);
        $csrf = spaceToken($client, '/perfil');
        checkSpace($client->get($path)->getStatusCode() === 403, "$role no puede consultar el registro administrativo");
        checkSpace($client->post($path, ['form_params' => array_merge(['_token' => $csrf], $base)])->getStatusCode() === 403 && $count() === $before, "$role no puede registrar espacios");
    }
    $db->prepare("UPDATE users SET estado = 'inactivo' WHERE id = ?")->execute([$accounts['Administrador']['id']]);
    checkSpace($admin->post($path, ['form_params' => array_merge(['_token' => $token], $base)])->getStatusCode() === 302 && $count() === $before, 'Administrador inactivo no puede registrar espacios');
    checkSpace($fresh->get($path)->getStatusCode() === 302, 'Administrador inactivo no puede consultar el módulo');
    $guest = spaceBrowser();
    checkSpace($guest->get($path)->getStatusCode() === 302, 'Invitado no puede consultar el módulo');
    checkSpace($guest->post($path, ['form_params' => array_merge(['_token' => spaceToken($guest, '/login')], $base)])->getStatusCode() === 302 && $count() === $before, 'Invitado no puede registrar espacios');
    echo "HU-19: registro de espacios comunes verificado.\n";
} finally {
    $db->prepare('DELETE FROM espacios_comunes WHERE nombre LIKE ?')->execute([$prefix.'%']);
    foreach ($created as $id) {
        $db->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    }
}
