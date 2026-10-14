<?php

// Prueba contra Nginx, PHP-FPM y MySQL reales. Ejecutar en el contenedor app.
require __DIR__.'/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

function check(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
    echo "PASS: $message\n";
}

function database(): PDO
{
    return new PDO(
        'mysql:host='.getenv('DB_HOST').';dbname='.getenv('DB_DATABASE'),
        getenv('DB_USERNAME'), getenv('DB_PASSWORD'),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
}

if (in_array('--fingerprint', $argv, true)) {
    // Comparar antes y después de recrear contenedores, sin revelar claves.
    $users = database()->query('SELECT id, password FROM users ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    echo hash('sha256', json_encode($users).file_get_contents(__DIR__.'/../storage/app/.app-key'))."\n";
    exit;
}

function browser(): Client
{
    return new Client([
        'base_uri' => 'http://web', 'cookies' => new CookieJar,
        'allow_redirects' => false, 'http_errors' => false, 'timeout' => 15,
    ]);
}

function csrf(Client $client): string
{
    $response = $client->get('/login');
    check($response->getStatusCode() === 200, 'El login responde HTTP 200');
    preg_match('/name="_token" value="([^"]+)"/', (string) $response->getBody(), $matches);
    if (empty($matches[1])) {
        throw new RuntimeException('Falta el token CSRF');
    }
    return $matches[1];
}

foreach (['admin', 'conserje', 'residente'] as $role) {
    $client = browser();
    $response = $client->post('/login', ['form_params' => [
        '_token' => csrf($client), 'identifier' => "$role@ediconnect.cl", 'password' => 'Password123!',
    ]]);
    check($response->getStatusCode() === 302 &&
        str_ends_with($response->getHeaderLine('Location'), "/$role/dashboard"), "Login de $role");
    check($client->get("/$role/dashboard")->getStatusCode() === 200, "Dashboard de $role");
    check($client->get('/login')->getStatusCode() === 302, "Middleware guest de $role");
    if ($role !== 'admin') {
        check($client->get('/admin/dashboard')->getStatusCode() === 403, "Permisos de $role");
    }
}

$client = browser();
check($client->get('/admin/dashboard')->getStatusCode() === 302, 'Invitado redirigido al login');
check($client->post('/login', ['form_params' => [
    'identifier' => 'admin@ediconnect.cl', 'password' => 'Password123!',
]])->getStatusCode() === 419, 'Protección CSRF activa');

$response = $client->post('/api/v1/auth/login', ['json' => [
    'email' => 'admin@ediconnect.cl', 'password' => 'Password123!',
]]);
$data = json_decode((string) $response->getBody(), true);
check($response->getStatusCode() === 200 && ! empty($data['token']), 'API emite un token Sanctum');
$statement = database()->prepare('DELETE FROM personal_access_tokens WHERE id = ?');
$statement->execute([explode('|', $data['token'])[0]]);

echo "Pruebas Docker completadas.\n";
