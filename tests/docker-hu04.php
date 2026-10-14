<?php

// Integración HU-04 con usuarios temporales; no modifica las cuentas de demostración.
require __DIR__.'/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

function verify(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
    echo "PASS: $message\n";
}

function sessionClient(): Client
{
    return new Client([
        'base_uri' => 'http://web', 'cookies' => new CookieJar,
        'allow_redirects' => false, 'http_errors' => false, 'timeout' => 15,
    ]);
}

function tokenFrom(Client $client, string $path): string
{
    $response = $client->get($path);
    if ($response->getStatusCode() !== 200 ||
        ! preg_match('/name="_token" value="([^"]+)"/', (string) $response->getBody(), $matches)) {
        throw new RuntimeException("No se pudo obtener CSRF desde $path");
    }
    return $matches[1];
}

function login(Client $client, string $email): \Psr\Http\Message\ResponseInterface
{
    return $client->post('/login', ['form_params' => [
        '_token' => tokenFrom($client, '/login'), 'identifier' => $email,
        'password' => 'PruebaHU04!123', 'remember' => '1',
    ]]);
}

$db = new PDO(
    'mysql:host='.getenv('DB_HOST').';dbname='.getenv('DB_DATABASE'),
    getenv('DB_USERNAME'), getenv('DB_PASSWORD'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$roles = $db->query('SELECT nombre, id FROM roles')->fetchAll(PDO::FETCH_KEY_PAIR);
$created = [];
$accounts = [];
$suffix = bin2hex(random_bytes(6));

try {
    foreach (['Administrador' => 'admin', 'Conserje' => 'conserje', 'Residente' => 'residente'] as $role => $slug) {
        $email = "hu04-$slug-$suffix@example.test";
        $insert = $db->prepare('INSERT INTO users (nombre, email, password, rol_id, estado, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
        $insert->execute(["HU04 $slug", $email, password_hash('PruebaHU04!123', PASSWORD_BCRYPT), $roles[$role], 'activo']);
        $created[] = $id = (int) $db->lastInsertId();
        $accounts[$slug] = ['id' => $id, 'email' => $email];
    }

    $admin = sessionClient();
    verify(login($admin, $accounts['admin']['email'])->getStatusCode() === 302, 'Administrador autenticado');
    $adminToken = tokenFrom($admin, '/admin/usuarios');
    $update = function (int $id, string $state, array $extra = []) use ($admin, $adminToken) {
        return $admin->patch("/admin/usuarios/$id/estado", array_merge([
            'headers' => ['Referer' => 'http://web/admin/usuarios'],
            'form_params' => ['_token' => $adminToken, 'estado' => $state],
        ], $extra));
    };
    $read = function (int $id) use ($db) {
        $query = $db->prepare('SELECT estado, rol_id, password, remember_token FROM users WHERE id = ?');
        $query->execute([$id]);
        return $query->fetch(PDO::FETCH_ASSOC);
    };

    foreach (['conserje', 'residente'] as $slug) {
        ['id' => $id, 'email' => $email] = $accounts[$slug];
        $user = sessionClient();
        login($user, $email);
        $userToken = tokenFrom($user, "/$slug/dashboard");
        verify($user->get('/admin/usuarios')->getStatusCode() === 403, "$slug no puede listar usuarios");
        $forbidden = $user->patch("/admin/usuarios/$id/estado", ['form_params' => [
            '_token' => $userToken, 'estado' => 'inactivo',
        ]]);
        verify($forbidden->getStatusCode() === 403 && $read($id)['estado'] === 'activo', "$slug no puede cambiar estados");

        $api = sessionClient();
        $apiLogin = $api->post('/api/v1/auth/login', ['json' => ['email' => $email, 'password' => 'PruebaHU04!123']]);
        verify($apiLogin->getStatusCode() === 200, "$slug tiene acceso API antes de desactivar");
        $before = $read($id);
        verify($update($id, 'inactivo')->getStatusCode() === 302 && $read($id)['estado'] === 'inactivo', "Administrador desactiva a $slug");
        $count = $db->prepare('SELECT COUNT(*) FROM personal_access_tokens WHERE tokenable_id = ? AND tokenable_type = ?');
        $count->execute([$id, 'App\\Models\\User']);
        verify((int) $count->fetchColumn() === 0, "Tokens de $slug revocados");
        verify($read($id)['remember_token'] !== $before['remember_token'], "Recordar sesión de $slug invalidado");
        verify($user->get("/$slug/dashboard")->getStatusCode() === 302, "Sesión abierta de $slug pierde acceso");
        $denied = login(sessionClient(), $email);
        verify($denied->getStatusCode() === 302 && str_ends_with($denied->getHeaderLine('Location'), '/login'), "Login web de $slug inactivo bloqueado");
        verify($api->post('/api/v1/auth/login', ['json' => ['email' => $email, 'password' => 'PruebaHU04!123']])->getStatusCode() === 403, "Login API de $slug inactivo bloqueado");

        verify($update($id, 'activo')->getStatusCode() === 302 && $read($id)['estado'] === 'activo', "Administrador reactiva a $slug");
        $after = $read($id);
        verify($before['rol_id'] === $after['rol_id'] && $before['password'] === $after['password'], "$slug conserva rol y contraseña");
        $restored = sessionClient();
        $redirect = login($restored, $email);
        verify(str_ends_with($redirect->getHeaderLine('Location'), "/$slug/dashboard") &&
            $restored->get("/$slug/dashboard")->getStatusCode() === 200, "$slug recupera el acceso según su rol");
        verify($api->post('/api/v1/auth/login', ['json' => ['email' => $email, 'password' => 'PruebaHU04!123']])->getStatusCode() === 200, "$slug recupera acceso API");
    }

    $target = $accounts['residente']['id'];
    $invalid = $update($target, 'suspendido', ['headers' => ['Accept' => 'application/json']]);
    verify($invalid->getStatusCode() === 422 && $read($target)['estado'] === 'activo', 'Estado inválido rechazado');
    $self = $accounts['admin']['id'];
    $update($self, 'inactivo');
    verify($read($self)['estado'] === 'activo' &&
        str_contains((string) $admin->get('/admin/usuarios')->getBody(), 'No puedes desactivar tu propia cuenta'), 'Administrador no puede desactivarse a sí mismo');
    verify($admin->patch("/admin/usuarios/$target/estado", ['form_params' => ['estado' => 'inactivo']])->getStatusCode() === 419, 'Cambio de estado requiere CSRF');
    $guest = sessionClient();
    verify($guest->get('/admin/usuarios')->getStatusCode() === 302, 'Invitado no puede listar usuarios');
    $denied = $guest->patch("/admin/usuarios/$target/estado", ['form_params' => [
        '_token' => tokenFrom($guest, '/login'), 'estado' => 'inactivo',
    ]]);
    verify($denied->getStatusCode() === 302 && $read($target)['estado'] === 'activo', 'Invitado no puede cambiar estados');
    verify($update(2147483647, 'activo')->getStatusCode() === 404, 'Usuario inexistente responde 404');
    echo "HU-04: criterios de aceptación verificados.\n";
} finally {
    foreach ($created as $id) {
        $db->prepare('DELETE FROM personal_access_tokens WHERE tokenable_id = ? AND tokenable_type = ?')->execute([$id, 'App\\Models\\User']);
        $db->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    }
}
