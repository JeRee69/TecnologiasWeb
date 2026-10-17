<?php

// Integración HU-07, HU-08, HU-09 y HU-10: Padrón de Departamentos
require __DIR__.'/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

function verify(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException("FALLÓ: $message");
    }
    echo "PASS: $message\n";
}

function sessionClient(): Client
{
    return new Client([
        'base_uri' => 'http://web',
        'cookies' => new CookieJar,
        'allow_redirects' => false,
        'http_errors' => false,
        'timeout' => 15,
    ]);
}

function tokenFrom(Client $client, string $path): string
{
    $response = $client->get($path);
    if ($response->getStatusCode() !== 200 ||
        ! preg_match('/name="_token" value="([^"]+)"/', (string) $response->getBody(), $matches)) {
        throw new RuntimeException("No se pudo obtener CSRF desde $path (Status: {$response->getStatusCode()})");
    }
    return $matches[1];
}

$db = new PDO(
    'mysql:host='.getenv('DB_HOST').';dbname='.getenv('DB_DATABASE'),
    getenv('DB_USERNAME'), getenv('DB_PASSWORD'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$roles = $db->query('SELECT nombre, id FROM roles')->fetchAll(PDO::FETCH_KEY_PAIR);
$suffix = bin2hex(random_bytes(4));
$createdUsers = [];
$createdDeptos = [];

try {
    // 1. Crear usuario Admin y Residente temporales
    $adminEmail = "admin-$suffix@test.com";
    $stmt = $db->prepare('INSERT INTO users (nombre, email, password, rol_id, estado, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
    $stmt->execute(['Admin Test', $adminEmail, password_hash('Secret123!', PASSWORD_BCRYPT), $roles['Administrador'], 'activo']);
    $adminId = (int) $db->lastInsertId();
    $createdUsers[] = $adminId;

    $residenteEmail = "residente-$suffix@test.com";
    $stmt->execute(['Residente Test', $residenteEmail, password_hash('Secret123!', PASSWORD_BCRYPT), $roles['Residente'], 'activo']);
    $residenteId = (int) $db->lastInsertId();
    $createdUsers[] = $residenteId;

    // Iniciar sesión con Admin
    $client = sessionClient();
    $loginResp = $client->post('/login', [
        'form_params' => [
            '_token' => tokenFrom($client, '/login'),
            'identifier' => $adminEmail,
            'password' => 'Secret123!',
        ],
    ]);
    verify($loginResp->getStatusCode() === 302, 'Login de administrador exitoso');

    // 2. HU-07: Listado y Registro de Departamento
    $listResp = $client->get('/admin/departamentos');
    verify($listResp->getStatusCode() === 200, 'Acceso a vista de departamentos OK');

    $deptoNumero = "T-$suffix";
    $csrf = tokenFrom($client, '/admin/departamentos');
    $createResp = $client->post('/admin/departamentos', [
        'form_params' => [
            '_token' => $csrf,
            'numero' => $deptoNumero,
            'piso' => 5,
            'estado' => 'activo',
        ],
    ]);
    verify($createResp->getStatusCode() === 302, 'HU-07: Registro de departamento exitoso (302 Redirect)');

    // Verificar en BD
    $depto = $db->query("SELECT * FROM departamentos WHERE numero = '$deptoNumero'")->fetch(PDO::FETCH_ASSOC);
    verify($depto && (int)$depto['piso'] === 5 && $depto['estado'] === 'activo', 'HU-07: Departamento persistido en BD correctamente');
    $deptoId = (int) $depto['id'];
    $createdDeptos[] = $deptoId;

    // Validación de número único
    $dupResp = $client->post('/admin/departamentos', [
        'form_params' => [
            '_token' => tokenFrom($client, '/admin/departamentos'),
            'numero' => $deptoNumero,
            'piso' => 6,
        ],
    ]);
    $followDup = $client->get('/admin/departamentos');
    verify(strpos((string)$followDup->getBody(), 'Ya existe un departamento registrado') !== false, 'HU-07: Validación de unicidad de número departamento efectiva');

    // 3. HU-08: Gestión de Estado (Activo / Inactivo)
    $patchResp = $client->patch("/admin/departamentos/$deptoId/estado", [
        'form_params' => [
            '_token' => tokenFrom($client, "/admin/departamentos/$deptoId"),
            'estado' => 'inactivo',
        ],
    ]);
    verify($patchResp->getStatusCode() === 302, 'HU-08: Desactivación de departamento enviada (302)');
    $estadoActual = $db->query("SELECT estado FROM departamentos WHERE id = $deptoId")->fetchColumn();
    verify($estadoActual === 'inactivo', 'HU-08: Estado actualizado a inactivo en BD');

    // Regla de Negocio Crítica: Intentar asociar residente a departamento INACTIVO -> DEBE SER RECHAZADO
    $asociarInactivoResp = $client->post("/admin/departamentos/$deptoId/residentes", [
        'form_params' => [
            '_token' => tokenFrom($client, "/admin/departamentos/$deptoId"),
            'residente_id' => $residenteId,
            'es_titular' => '1',
        ],
    ]);
    $showBodyInactivo = (string) $client->get("/admin/departamentos/$deptoId")->getBody();
    verify(strpos($showBodyInactivo, 'No es posible asociar residentes a un departamento inactivo') !== false, 'HU-08/HU-09: Rechazo exitoso al intentar asociar residente a departamento inactivo');

    // Reactivar departamento
    $client->patch("/admin/departamentos/$deptoId/estado", [
        'form_params' => [
            '_token' => tokenFrom($client, "/admin/departamentos/$deptoId"),
            'estado' => 'activo',
        ],
    ]);
    $estadoActual = $db->query("SELECT estado FROM departamentos WHERE id = $deptoId")->fetchColumn();
    verify($estadoActual === 'activo', 'HU-08: Reactivación de departamento exitosa');

    // 4. HU-09: Asociación de residente a departamento ACTIVO
    $asociarActivoResp = $client->post("/admin/departamentos/$deptoId/residentes", [
        'form_params' => [
            '_token' => tokenFrom($client, "/admin/departamentos/$deptoId"),
            'residente_id' => $residenteId,
            'es_titular' => '1',
        ],
    ]);
    verify($asociarActivoResp->getStatusCode() === 302, 'HU-09: Asociación de residente procesada (302)');

    // Verificar en tabla pivote
    $pivote = $db->query("SELECT * FROM departamento_user WHERE departamento_id = $deptoId AND user_id = $residenteId")->fetch(PDO::FETCH_ASSOC);
    verify($pivote && (bool)$pivote['es_titular'] === true, 'HU-09: Residente asociado en BD como titular');

    // 5. HU-10: Contacto de Emergencia
    $contactoResp = $client->post("/admin/departamentos/$deptoId/contactos", [
        'form_params' => [
            '_token' => tokenFrom($client, "/admin/departamentos/$deptoId"),
            'nombre' => 'Dra. Claudia Morales',
            'telefono' => '+56912345678',
            'relacion' => 'Madre',
            'user_id' => $residenteId,
        ],
    ]);
    verify($contactoResp->getStatusCode() === 302, 'HU-10: Registro de contacto de emergencia procesado (302)');

    $contacto = $db->query("SELECT * FROM contactos_emergencia WHERE departamento_id = $deptoId AND nombre = 'Dra. Claudia Morales'")->fetch(PDO::FETCH_ASSOC);
    verify($contacto && $contacto['telefono'] === '+56912345678' && $contacto['relacion'] === 'Madre', 'HU-10: Contacto de emergencia guardado en BD con datos válidos');
    $contactoId = (int) $contacto['id'];

    // Eliminar contacto de emergencia
    $delContactoResp = $client->delete("/admin/departamentos/$deptoId/contactos/$contactoId", [
        'form_params' => [
            '_token' => tokenFrom($client, "/admin/departamentos/$deptoId"),
        ],
    ]);
    verify($delContactoResp->getStatusCode() === 302, 'HU-10: Eliminación de contacto procesada (302)');
    $contactoExiste = $db->query("SELECT COUNT(*) FROM contactos_emergencia WHERE id = $contactoId")->fetchColumn();
    verify((int)$contactoExiste === 0, 'HU-10: Contacto eliminado exitosamente de BD');

    // Desasociar residente (HU-09)
    $desasociarResp = $client->delete("/admin/departamentos/$deptoId/residentes/$residenteId", [
        'form_params' => [
            '_token' => tokenFrom($client, "/admin/departamentos/$deptoId"),
        ],
    ]);
    verify($desasociarResp->getStatusCode() === 302, 'HU-09: Desasociación de residente procesada (302)');
    $pivotExiste = $db->query("SELECT COUNT(*) FROM departamento_user WHERE departamento_id = $deptoId AND user_id = $residenteId")->fetchColumn();
    verify((int)$pivotExiste === 0, 'HU-09: Residente desasociado exitosamente de la BD');

    echo "\n>>> TODOS LOS TESTS DE HU-07, HU-08, HU-09 Y HU-10 PASARON EXITOSAMENTE <<<\n";
} finally {
    // Limpieza
    foreach ($createdDeptos as $dId) {
        $db->exec("DELETE FROM departamentos WHERE id = $dId");
    }
    foreach ($createdUsers as $uId) {
        $db->exec("DELETE FROM users WHERE id = $uId");
    }
}
