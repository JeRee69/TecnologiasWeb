<?php

// Integración HU-25: Registro de encomiendas recibidas en conserjería
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
        throw new RuntimeException("No se pudo obtener CSRF desde $path");
    }
    return $matches[1];
}

function login(Client $client, string $email, string $password = 'PruebaHU25!123'): \Psr\Http\Message\ResponseInterface
{
    return $client->post('/login', ['form_params' => [
        '_token' => tokenFrom($client, '/login'),
        'identifier' => $email,
        'password' => $password,
        'remember' => '1',
    ]]);
}

$db = new PDO(
    'mysql:host='.getenv('DB_HOST').';dbname='.getenv('DB_DATABASE'),
    getenv('DB_USERNAME'),
    getenv('DB_PASSWORD'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$roles = $db->query('SELECT nombre, id FROM roles')->fetchAll(PDO::FETCH_KEY_PAIR);
$suffix = bin2hex(random_bytes(6));
$createdUserIds = [];
$createdDeptoIds = [];
$createdEncomiendaIds = [];

try {
    // 1. Crear conserje y residente para las pruebas
    $conserjeEmail = "conserje-hu25-$suffix@example.test";
    $insertConserje = $db->prepare('INSERT INTO users (nombre, email, password, rol_id, estado, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
    $insertConserje->execute(['Conserje HU25', $conserjeEmail, password_hash('PruebaHU25!123', PASSWORD_BCRYPT), $roles['Conserje'], 'activo']);
    $createdUserIds[] = $conserjeId = (int) $db->lastInsertId();

    $residenteEmail = "residente-hu25-$suffix@example.test";
    $insertRes = $db->prepare('INSERT INTO users (nombre, email, password, rol_id, estado, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
    $insertRes->execute(['Residente HU25', $residenteEmail, password_hash('PruebaHU25!123', PASSWORD_BCRYPT), $roles['Residente'], 'activo']);
    $createdUserIds[] = (int) $db->lastInsertId();

    // 2. Crear departamentos de prueba (uno activo y uno inactivo)
    $insertDepto = $db->prepare('INSERT INTO departamentos (numero, piso, estado, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())');
    $insertDepto->execute(["901-$suffix", 9, 'activo']);
    $createdDeptoIds[] = $deptoActivoId = (int) $db->lastInsertId();

    $insertDepto->execute(["902-$suffix", 9, 'inactivo']);
    $createdDeptoIds[] = $deptoInactivoId = (int) $db->lastInsertId();

    // 3. Conserje inicia sesión
    $conserjeClient = sessionClient();
    $loginRes = login($conserjeClient, $conserjeEmail);
    verify($loginRes->getStatusCode() === 302, 'Conserje inicia sesión exitosamente');

    // 4. Conserje visualiza la bitácora de encomiendas
    $vistaBitacora = $conserjeClient->get('/conserje/encomiendas');
    verify($vistaBitacora->getStatusCode() === 200, 'Conserje accede a /conserje/encomiendas (HTTP 200)');
    $htmlBitacora = (string) $vistaBitacora->getBody();
    verify(str_contains($htmlBitacora, 'Registrar encomienda'), 'Formulario de encomiendas visible');
    verify(str_contains($htmlBitacora, "901-$suffix"), 'Departamento activo presente en el selector');
    verify(! str_contains($htmlBitacora, "902-$suffix"), 'Departamento inactivo excluido del selector');

    // 5. Registrar encomienda con departamento, fecha, hora y empresa de delivery (HU-25)
    $csrf = tokenFrom($conserjeClient, '/conserje/encomiendas');
    $postEncomienda = $conserjeClient->post('/conserje/encomiendas', [
        'form_params' => [
            '_token' => $csrf,
            'departamento_id' => $deptoActivoId,
            'empresa_delivery' => 'Chilexpress',
            'fecha' => '2026-10-19',
            'hora' => '15:30',
            'codigo_seguimiento' => 'CHX-123456',
            'descripcion' => 'Caja sellada mediana',
        ],
    ]);
    verify($postEncomienda->getStatusCode() === 302, 'Registro de encomienda redirige tras guardar');

    // Comprobar persistencia en base de datos
    $stmt = $db->prepare('SELECT * FROM encomiendas WHERE departamento_id = ? AND codigo_seguimiento = ?');
    $stmt->execute([$deptoActivoId, 'CHX-123456']);
    $encomiendaBD = $stmt->fetch(PDO::FETCH_ASSOC);
    verify($encomiendaBD !== false, 'Encomienda persistida en la base de datos');
    $createdEncomiendaIds[] = (int) $encomiendaBD['id'];

    verify((int) $encomiendaBD['departamento_id'] === $deptoActivoId, 'La encomienda queda asociada al departamento indicado');
    verify($encomiendaBD['empresa_delivery'] === 'Chilexpress', 'Empresa de delivery registrada correctamente');
    verify($encomiendaBD['estado'] === 'Ingresado', 'Estado inicial es "Ingresado"');
    verify(str_contains($encomiendaBD['fecha_ingreso'], '2026-10-19 15:30'), 'Fecha y hora registradas correctamente');
    verify((int) $encomiendaBD['conserje_id'] === $conserjeId, 'Conserje receptor asociado');

    // 6. Verificar que la encomienda aparece en la bitácora
    $vistaActualizada = $conserjeClient->get('/conserje/encomiendas');
    $htmlActualizada = (string) $vistaActualizada->getBody();
    verify(str_contains($htmlActualizada, "Depto. 901-$suffix"), 'Departamento visible en la bitácora');
    verify(str_contains($htmlActualizada, 'Chilexpress'), 'Empresa de delivery visible en la bitácora');
    verify(str_contains($htmlActualizada, 'CHX-123456'), 'Código de seguimiento visible en la bitácora');
    verify(str_contains($htmlActualizada, 'Ingresado'), 'Badge de estado inicial Ingresado visible');

    // 7. Registro sin especificar fecha/hora (toma momento actual)
    $csrf2 = tokenFrom($conserjeClient, '/conserje/encomiendas');
    $postEncomienda2 = $conserjeClient->post('/conserje/encomiendas', [
        'form_params' => [
            '_token' => $csrf2,
            'departamento_id' => $deptoActivoId,
            'empresa_delivery' => 'Mercado Libre',
        ],
    ]);
    verify($postEncomienda2->getStatusCode() === 302, 'Registro sin fecha/hora manual responde 302');
    $stmt2 = $db->prepare('SELECT * FROM encomiendas WHERE departamento_id = ? AND empresa_delivery = ? ORDER BY id DESC LIMIT 1');
    $stmt2->execute([$deptoActivoId, 'Mercado Libre']);
    $encomiendaBD2 = $stmt2->fetch(PDO::FETCH_ASSOC);
    verify($encomiendaBD2 !== false, 'Segunda encomienda persistida');
    $createdEncomiendaIds[] = (int) $encomiendaBD2['id'];
    verify(! empty($encomiendaBD2['fecha_ingreso']), 'Fecha de ingreso asignada automáticamente');
    verify($encomiendaBD2['estado'] === 'Ingresado', 'Segunda encomienda con estado inicial Ingresado');

    // 8. Validación: departamento inactivo rechazado
    $csrfInactivo = tokenFrom($conserjeClient, '/conserje/encomiendas');
    $postInactivo = $conserjeClient->post('/conserje/encomiendas', [
        'form_params' => [
            '_token' => $csrfInactivo,
            'departamento_id' => $deptoInactivoId,
            'empresa_delivery' => 'Starken',
        ],
    ]);
    verify($postInactivo->getStatusCode() === 302, 'Departamento inactivo es rechazado');

    // 9. Validación: empresa_delivery requerida
    $csrfSinEmpresa = tokenFrom($conserjeClient, '/conserje/encomiendas');
    $postSinEmpresa = $conserjeClient->post('/conserje/encomiendas', [
        'form_params' => [
            '_token' => $csrfSinEmpresa,
            'departamento_id' => $deptoActivoId,
            'empresa_delivery' => '',
        ],
    ]);
    verify($postSinEmpresa->getStatusCode() === 302, 'Empresa de delivery vacía es rechazada');

    // 10. Endpoint API: POST /api/v1/encomiendas
    $apiClient = new Client([
        'base_uri' => 'http://web',
        'http_errors' => false,
        'headers' => ['Accept' => 'application/json'],
    ]);
    $apiRes = $apiClient->post('/api/v1/encomiendas', [
        'json' => [
            'departamento_id' => $deptoActivoId,
            'empresa_delivery' => 'Blue Express',
        ],
    ]);
    verify($apiRes->getStatusCode() === 201, 'Endpoint API responde HTTP 201 Created');
    $apiData = json_decode((string) $apiRes->getBody(), true);
    verify(isset($apiData['id']) && $apiData['estado'] === 'ingresado', 'API devuelve { id, estado: "ingresado" }');
    $createdEncomiendaIds[] = (int) $apiData['id'];

    // 11. Endpoint API: payload inválido responde 400
    $apiBadRes = $apiClient->post('/api/v1/encomiendas', [
        'json' => [
            'departamento_id' => 999999, // Inexistente
            'empresa_delivery' => '',
        ],
    ]);
    verify($apiBadRes->getStatusCode() === 400, 'API rechaza payload inválido con HTTP 400');

    // 12. Control RBAC: Residente no puede acceder a /conserje/encomiendas (HTTP 403)
    $residenteClient = sessionClient();
    login($residenteClient, $residenteEmail);
    $residenteGet = $residenteClient->get('/conserje/encomiendas');
    verify($residenteGet->getStatusCode() === 403, 'Residente bloqueado para ver bitácora de conserje (HTTP 403)');

    // 13. Visitante no autenticado es redirigido a login
    $guestClient = sessionClient();
    $guestGet = $guestClient->get('/conserje/encomiendas');
    verify($guestGet->getStatusCode() === 302, 'Invitado redirigido al login');

    echo "HU-25: criterios de aceptación verificados exitosamente.\n";

} finally {
    if (! empty($createdEncomiendaIds)) {
        $placeholders = implode(',', array_fill(0, count($createdEncomiendaIds), '?'));
        $db->prepare("DELETE FROM encomiendas WHERE id IN ($placeholders)")->execute($createdEncomiendaIds);
    }
    if (! empty($createdDeptoIds)) {
        $placeholders = implode(',', array_fill(0, count($createdDeptoIds), '?'));
        $db->prepare("DELETE FROM departamentos WHERE id IN ($placeholders)")->execute($createdDeptoIds);
    }
    if (! empty($createdUserIds)) {
        $placeholders = implode(',', array_fill(0, count($createdUserIds), '?'));
        $db->prepare("DELETE FROM users WHERE id IN ($placeholders)")->execute($createdUserIds);
    }
}
