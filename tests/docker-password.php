<?php

// Cambio de contraseña real por HTTP, exclusivamente con cuentas temporales.
require __DIR__.'/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

function passwordCheck(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
    echo "PASS: $message\n";
}

function passwordBrowser(?CookieJar $cookies = null): Client
{
    return new Client(['base_uri' => 'http://web', 'cookies' => $cookies ?? new CookieJar,
        'allow_redirects' => false, 'http_errors' => false, 'timeout' => 15]);
}

function passwordCsrf(Client $client, string $path): string
{
    $response = $client->get($path);
    if ($response->getStatusCode() !== 200 ||
        ! preg_match('/name="_token" value="([^"]+)"/', (string) $response->getBody(), $matches)) {
        throw new RuntimeException("No se pudo abrir $path");
    }
    return $matches[1];
}

function passwordLogin(Client $client, string $email, string $password): \Psr\Http\Message\ResponseInterface
{
    return $client->post('/login', ['form_params' => [
        '_token' => passwordCsrf($client, '/login'), 'identifier' => $email, 'password' => $password, 'remember' => '1',
    ]]);
}

$db = new PDO('mysql:host='.getenv('DB_HOST').';dbname='.getenv('DB_DATABASE'),
    getenv('DB_USERNAME'), getenv('DB_PASSWORD'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$roles = $db->query('SELECT nombre, id FROM roles')->fetchAll(PDO::FETCH_KEY_PAIR);
$created = [];
$suffix = bin2hex(random_bytes(6));
$old = 'InicialPersonal!123';
$new = 'NuevaPersonal!456';

try {
    foreach ($roles as $role => $roleId) {
        $email = "clave-$roleId-$suffix@example.test";
        $db->prepare('INSERT INTO users (nombre, email, password, rol_id, estado, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())')
            ->execute(["Clave $role", $email, password_hash($old, PASSWORD_BCRYPT), $roleId, 'activo']);
        $created[] = $id = (int) $db->lastInsertId();
        $read = function () use ($db, $id) {
            $query = $db->prepare('SELECT * FROM users WHERE id = ?');
            $query->execute([$id]);
            return $query->fetch(PDO::FETCH_ASSOC);
        };
        $client = passwordBrowser();
        passwordLogin($client, $email, $old);
        $token = passwordCsrf($client, '/perfil');
        $other = passwordBrowser();
        passwordLogin($other, $email, $old);
        // No visitar otra página: incluso una sesión recién abierta debe caducar.
        $remember = new CookieJar(false, array_values(array_filter($other->getConfig('cookies')->toArray(),
            fn ($cookie) => str_starts_with($cookie['Name'], 'remember_web_'))));
        $before = $read();
        $send = fn (array $data, bool $json = true) => $client->patch('/perfil/contrasena', [
            'headers' => $json ? ['Accept' => 'application/json'] : ['Referer' => 'http://web/perfil'],
            'form_params' => array_merge(['_token' => $token, 'current_password' => $old,
                'password' => $new, 'password_confirmation' => $new], $data),
        ]);
        foreach ([
            ['current_password' => 'Incorrecta!123'],
            ['password_confirmation' => 'NoCoincide!123'],
            ['password' => 'corta', 'password_confirmation' => 'corta'],
            ['password' => $old, 'password_confirmation' => $old],
            ['password' => str_repeat('a', 73), 'password_confirmation' => str_repeat('a', 73)],
            ['password' => ['no-es-texto']],
        ] as $invalid) {
            passwordCheck($send($invalid)->getStatusCode() === 422 && $before === $read(), "$role rechaza cambio inválido sin modificar datos");
        }
        $send(['current_password' => 'Incorrecta!123'], false);
        $html = (string) $client->get('/perfil')->getBody();
        passwordCheck(str_contains($html, 'La contraseña actual es incorrecta.') &&
            ! str_contains($html, 'value="'.$new.'"') && ! str_contains($html, 'value="Incorrecta!123"'), "$role muestra error sin volver a mostrar contraseñas");

        $api = passwordBrowser();
        passwordCheck($api->post('/api/v1/auth/login', ['json' => ['email' => $email, 'password' => $old]])->getStatusCode() === 200, "$role obtiene token antes del cambio");
        $db->prepare('INSERT INTO password_reset_tokens (email, token, created_at) VALUES (?, ?, NOW())')
            ->execute([$email, password_hash('enlace-pendiente', PASSWORD_BCRYPT)]);
        passwordCheck($send([], false)->getStatusCode() === 302, "$role actualiza su contraseña");
        $after = $read();
        passwordCheck(password_verify($new, $after['password']) && ! password_verify($old, $after['password']) && $after['password'] !== $new, "$role guarda la nueva contraseña mediante hash");
        foreach (['nombre', 'email', 'telefono', 'email_secundario', 'rol_id', 'estado'] as $field) {
            passwordCheck($before[$field] === $after[$field], "$role conserva $field");
        }
        passwordCheck($before['remember_token'] !== $after['remember_token'], "$role invalida los accesos recordados");
        $count = $db->prepare('SELECT COUNT(*) FROM personal_access_tokens WHERE tokenable_id = ? AND tokenable_type = ?');
        $count->execute([$id, 'App\\Models\\User']);
        passwordCheck((int) $count->fetchColumn() === 0, "$role revoca tokens API previos");
        $count = $db->prepare('SELECT COUNT(*) FROM password_reset_tokens WHERE email = ?');
        $count->execute([$email]);
        passwordCheck((int) $count->fetchColumn() === 0, "$role invalida enlaces de recuperación pendientes");
        passwordCheck($client->get('/perfil')->getStatusCode() === 200, "$role conserva su sesión actual");
        passwordCheck($other->get('/perfil')->getStatusCode() === 302, "$role cierra la otra sesión en su próxima petición");
        passwordCheck(passwordBrowser($remember)->get('/perfil')->getStatusCode() === 302, "$role bloquea la cookie recordada anterior");
        $denied = passwordLogin(passwordBrowser(), $email, $old);
        passwordCheck($denied->getStatusCode() === 302 && str_ends_with($denied->getHeaderLine('Location'), '/login'), "$role no puede ingresar con la contraseña anterior");
        $fresh = passwordBrowser();
        $success = passwordLogin($fresh, $email, $new);
        passwordCheck($success->getStatusCode() === 302 && $fresh->get('/perfil')->getStatusCode() === 200, "$role puede ingresar con la nueva contraseña");
        passwordCheck($api->post('/api/v1/auth/login', ['json' => ['email' => $email, 'password' => $old]])->getStatusCode() === 401, "$role rechaza la contraseña anterior en API");
        passwordCheck($api->post('/api/v1/auth/login', ['json' => ['email' => $email, 'password' => $new]])->getStatusCode() === 200, "$role acepta la contraseña nueva en API");
        passwordCheck($fresh->patch('/perfil/contrasena', ['form_params' => ['current_password' => $new]])->getStatusCode() === 419, "$role requiere CSRF");
        $freshToken = passwordCsrf($fresh, '/perfil');
        $db->prepare("UPDATE users SET estado = 'inactivo' WHERE id = ?")->execute([$id]);
        passwordCheck($fresh->patch('/perfil/contrasena', ['form_params' => ['_token' => $freshToken,
            'current_password' => $new, 'password' => $old, 'password_confirmation' => $old]])->getStatusCode() === 302 &&
            password_verify($new, $read()['password']), "$role inactivo no puede cambiar contraseña");
    }
    $guest = passwordBrowser();
    passwordCheck($guest->patch('/perfil/contrasena', ['form_params' => ['_token' => passwordCsrf($guest, '/login'),
        'current_password' => $old, 'password' => $new, 'password_confirmation' => $new]])->getStatusCode() === 302, 'Invitado no puede cambiar contraseñas');
    echo "Cambio de contraseña: pruebas completadas.\n";
} finally {
    foreach ($created as $id) {
        $db->prepare('DELETE FROM password_reset_tokens WHERE email = (SELECT email FROM users WHERE id = ?)')->execute([$id]);
        $db->prepare('DELETE FROM personal_access_tokens WHERE tokenable_id = ? AND tokenable_type = ?')->execute([$id, 'App\\Models\\User']);
        $db->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    }
}
