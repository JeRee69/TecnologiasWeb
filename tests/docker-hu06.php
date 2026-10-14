<?php

// Pruebas HTTP de HU-06 con usuarios temporales y limpieza al terminar.
require __DIR__.'/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

function verifyContact(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
    echo "PASS: $message\n";
}

function contactBrowser(): Client
{
    return new Client([
        'base_uri' => 'http://web', 'cookies' => new CookieJar,
        'allow_redirects' => false, 'http_errors' => false, 'timeout' => 15,
    ]);
}

function contactCsrf(Client $client, string $path): string
{
    $response = $client->get($path);
    if ($response->getStatusCode() !== 200 ||
        ! preg_match('/name="_token" value="([^"]+)"/', (string) $response->getBody(), $matches)) {
        throw new RuntimeException("No se pudo abrir $path");
    }
    return $matches[1];
}

function contactLogin(string $email): Client
{
    $client = contactBrowser();
    $response = $client->post('/login', ['form_params' => [
        '_token' => contactCsrf($client, '/login'), 'identifier' => $email, 'password' => 'PruebaHU06!123',
    ]]);
    verifyContact($response->getStatusCode() === 302 && ! str_ends_with($response->getHeaderLine('Location'), '/login'), 'Login con correo de acceso original');
    return $client;
}

$db = new PDO('mysql:host='.getenv('DB_HOST').';dbname='.getenv('DB_DATABASE'),
    getenv('DB_USERNAME'), getenv('DB_PASSWORD'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$roles = $db->query('SELECT nombre, id FROM roles')->fetchAll(PDO::FETCH_KEY_PAIR);
$created = [];
$accounts = [];
$suffix = bin2hex(random_bytes(6));
$read = function (int $id) use ($db) {
    $query = $db->prepare('SELECT * FROM users WHERE id = ?');
    $query->execute([$id]);
    return $query->fetch(PDO::FETCH_ASSOC);
};

try {
    foreach (['Administrador', 'Conserje', 'Residente'] as $role) {
        $email = strtolower($role)."-$suffix@example.test";
        $insert = $db->prepare('INSERT INTO users (nombre, email, password, rol_id, estado, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
        $insert->execute(["HU06 $role", $email, password_hash('PruebaHU06!123', PASSWORD_BCRYPT), $roles[$role], 'activo']);
        $created[] = $id = (int) $db->lastInsertId();
        $accounts[$role] = ['id' => $id, 'email' => $email];
    }

    foreach ($accounts as $role => ['id' => $id, 'email' => $email]) {
        $client = contactLogin($email);
        $token = contactCsrf($client, '/perfil');
        $before = $read($id);
        $otherId = $accounts[$role === 'Residente' ? 'Conserje' : 'Residente']['id'];
        $otherBefore = $read($otherId);
        $secondary = "contacto-$id@example.test";
        $response = $client->patch('/perfil', ['form_params' => [
            '_token' => $token, 'telefono' => '+56 9 8765 4321', 'email_secundario' => $secondary,
            'id' => $otherId, 'user_id' => $otherId, 'nombre' => 'Nombre alterado',
            'rut' => '99.999.999-9', 'email' => 'otro@example.test',
            'rol_id' => $roles['Administrador'], 'estado' => 'inactivo', 'password' => 'Alterada123!',
        ]]);
        verifyContact($response->getStatusCode() === 302 && str_ends_with($response->getHeaderLine('Location'), '/perfil'), "$role guarda su contacto");
        $after = $read($id);
        verifyContact($after['telefono'] === '+56 9 8765 4321' && $after['email_secundario'] === $secondary, "$role persiste los cambios");
        foreach (['nombre', 'rut', 'email', 'rol_id', 'estado', 'password'] as $field) {
            verifyContact($before[$field] === $after[$field], "$role no puede cambiar $field desde su perfil");
        }
        verifyContact($otherBefore === $read($otherId), "$role no puede modificar la cuenta ajena indicada en la petición");
        $fresh = contactLogin($email);
        $html = (string) $fresh->get('/perfil')->getBody();
        verifyContact(str_contains($html, 'value="+56 9 8765 4321"') && str_contains($html, 'value="'.$secondary.'"'), "$role consulta los cambios desde una sesión nueva");

        foreach ([
            ['telefono' => 'abc', 'email_secundario' => 'no-es-correo'],
            ['telefono' => '123'],
            ['telefono' => '1234567890123456'],
            ['telefono' => '-------'],
            ['telefono' => ['123456789']],
            ['telefono' => str_repeat('1', 31)],
            ['telefono' => '987654321', 'email_secundario' => 'incorrecto'],
            ['email_secundario' => ['otro@example.test']],
            ['email_secundario' => str_repeat('a', 255).'@example.test'],
        ] as $invalid) {
            $response = $client->patch('/perfil', ['headers' => ['Accept' => 'application/json'],
                'form_params' => array_merge(['_token' => $token], $invalid)]);
            verifyContact($response->getStatusCode() === 422 && $read($id) === $after, "$role rechaza datos inválidos sin guardar cambios parciales");
        }

        // Comprobar errores en la interfaz y preservación del dato escrito.
        $client->patch('/perfil', ['headers' => ['Referer' => 'http://web/perfil'],
            'form_params' => ['_token' => $token, 'telefono' => '123', 'email_secundario' => $secondary]]);
        $errors = (string) $client->get('/perfil')->getBody();
        verifyContact(str_contains($errors, 'entre 7 y 15 dígitos') && str_contains($errors, 'value="123"'), "$role ve el error y conserva su entrada");

        $client->patch('/perfil', ['form_params' => ['_token' => $token, 'telefono' => '912345678']]);
        verifyContact($read($id)['telefono'] === '912345678' && $read($id)['email_secundario'] === $secondary, "$role puede actualizar un solo campo");
        $client->patch('/perfil', ['form_params' => ['_token' => $token, 'telefono' => '', 'email_secundario' => '']]);
        verifyContact($read($id)['telefono'] === null && $read($id)['email_secundario'] === null, "$role puede eliminar contactos opcionales");
        verifyContact($client->patch('/perfil', ['form_params' => ['telefono' => '912345678']])->getStatusCode() === 419, "$role necesita CSRF para guardar");

        $db->prepare("UPDATE users SET estado = 'inactivo' WHERE id = ?")->execute([$id]);
        $denied = $client->patch('/perfil', ['form_params' => ['_token' => $token, 'telefono' => '912345678']]);
        verifyContact($denied->getStatusCode() === 302 && $read($id)['telefono'] === null, "$role desactivado no puede modificar su perfil");
        verifyContact($fresh->get('/perfil')->getStatusCode() === 302, "$role desactivado no puede consultar su perfil");
    }

    $guest = contactBrowser();
    verifyContact($guest->get('/perfil')->getStatusCode() === 302, 'Invitado no puede consultar perfiles');
    $response = $guest->patch('/perfil', ['form_params' => ['_token' => contactCsrf($guest, '/login'), 'telefono' => '912345678']]);
    verifyContact($response->getStatusCode() === 302, 'Invitado no puede actualizar perfiles');
    echo "HU-06: criterios de aceptación verificados.\n";
} finally {
    foreach ($created as $id) {
        $db->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    }
}
