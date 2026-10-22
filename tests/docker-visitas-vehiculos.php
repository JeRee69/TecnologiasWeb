<?php

// Integración HU-29 y HU-30: Registro de Visitas y Vehículos Autorizados en Conserjería
require __DIR__.'/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

function verify(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException("FAIL: $message");
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

function login(Client $client, string $email, string $password = 'PruebaHU2930!123'): \Psr\Http\Message\ResponseInterface
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
$createdVisitaIds = [];
$createdVehiculoIds = [];

try {
    echo "--- INICIANDO SUITE DE PRUEBAS HU-29 Y HU-30 (CONSERJERÍA) ---\n";

    // 1. Configuración de usuarios y departamentos de prueba
    $conserjeEmail = "conserje-hu2930-$suffix@example.test";
    $insertConserje = $db->prepare('INSERT INTO users (nombre, email, password, rol_id, estado, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
    $insertConserje->execute(['Conserje Test', $conserjeEmail, password_hash('PruebaHU2930!123', PASSWORD_BCRYPT), $roles['Conserje'], 'activo']);
    $createdUserIds[] = $conserjeId = (int) $db->lastInsertId();

    $residenteEmail = "residente-hu2930-$suffix@example.test";
    $insertRes = $db->prepare('INSERT INTO users (nombre, email, password, rol_id, estado, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
    $insertRes->execute(['Residente Test', $residenteEmail, password_hash('PruebaHU2930!123', PASSWORD_BCRYPT), $roles['Residente'], 'activo']);
    $createdUserIds[] = (int) $db->lastInsertId();

    // Departamentos: activo e inactivo
    $insertDepto = $db->prepare('INSERT INTO departamentos (numero, piso, estado, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())');
    $insertDepto->execute(["801-$suffix", 8, 'activo']);
    $createdDeptoIds[] = $deptoActivoId = (int) $db->lastInsertId();

    $insertDepto->execute(["802-$suffix", 8, 'inactivo']);
    $createdDeptoIds[] = $deptoInactivoId = (int) $db->lastInsertId();

    // 2. Conserje inicia sesión
    $conserjeClient = sessionClient();
    $loginRes = login($conserjeClient, $conserjeEmail);
    verify($loginRes->getStatusCode() === 302, 'Conserje inicia sesión correctamente en el sistema');

    // ==========================================
    // PRUEBAS HU-29: REGISTRO DE VISITAS
    // ==========================================
    echo "\n-- Verificando HU-29: Control y Bitácora de Visitas --\n";

    // 2.1 Acceso a la bitácora de visitas
    $vistaVisitas = $conserjeClient->get('/conserje/visitas');
    verify($vistaVisitas->getStatusCode() === 200, 'Conserje accede a /conserje/visitas (HTTP 200)');
    $htmlVisitas = (string) $vistaVisitas->getBody();
    verify(str_contains($htmlVisitas, 'Registrar Visita'), 'Formulario de registro de visita presente');
    verify(str_contains($htmlVisitas, "801-$suffix"), 'Departamento activo presente en el selector');
    verify(! str_contains($htmlVisitas, "802-$suffix"), 'Departamento inactivo excluido del selector de visitas');

    // 2.2 Registrar visita exitosa en departamento activo
    $csrfVisita = tokenFrom($conserjeClient, '/conserje/visitas');
    $postVisita = $conserjeClient->post('/conserje/visitas', [
        'form_params' => [
            '_token' => $csrfVisita,
            'departamento_id' => $deptoActivoId,
            'nombre_visitante' => 'Francisca Valenzuela',
            'rut_visitante' => '17.987.654-3',
            'fecha' => '2026-10-22',
            'hora' => '14:15',
            'observaciones' => 'Entrega de documentos personales',
        ],
    ]);
    verify($postVisita->getStatusCode() === 302, 'Registro de visita responde 302 Redirect');

    // Comprobar persistencia en BD
    $stmtVisita = $db->prepare('SELECT * FROM visitas WHERE departamento_id = ? AND nombre_visitante = ?');
    $stmtVisita->execute([$deptoActivoId, 'Francisca Valenzuela']);
    $visitaBD = $stmtVisita->fetch(PDO::FETCH_ASSOC);
    verify($visitaBD !== false, 'Visita persistida exitosamente en la base de datos');
    $createdVisitaIds[] = (int) $visitaBD['id'];

    verify((int) $visitaBD['departamento_id'] === $deptoActivoId, 'Visita asociada al departamento correcto');
    verify((int) $visitaBD['conserje_id'] === $conserjeId, 'Conserje en turno asignado a la visita');
    verify($visitaBD['rut_visitante'] === '17.987.654-3', 'RUT/identificación registrada');
    verify($visitaBD['estado'] === 'En Recinto', 'Estado inicial de la visita es "En Recinto"');
    verify(empty($visitaBD['fecha_salida']), 'Fecha de salida inicialmente nula');

    // 2.3 Visualización en bitácora
    $vistaVisitasActualizada = $conserjeClient->get('/conserje/visitas');
    $htmlActualizada = (string) $vistaVisitasActualizada->getBody();
    verify(str_contains($htmlActualizada, 'Francisca Valenzuela'), 'Nombre del visitante visible en la bitácora');
    verify(str_contains($htmlActualizada, '17.987.654-3'), 'RUT visible en la bitácora');
    verify(str_contains($htmlActualizada, "Depto. 801-$suffix"), 'Departamento visible en la bitácora');
    verify(str_contains($htmlActualizada, 'En Recinto'), 'Badge En Recinto visible');

    // 2.4 Registrar salida de la visita
    $csrfSalida = tokenFrom($conserjeClient, '/conserje/visitas');
    $patchSalida = $conserjeClient->patch('/conserje/visitas/'.$visitaBD['id'].'/salida', [
        'form_params' => [
            '_token' => $csrfSalida,
        ],
    ]);
    verify($patchSalida->getStatusCode() === 302, 'Registro de salida responde 302 Redirect');

    // Comprobar actualización en BD
    $stmtSalida = $db->prepare('SELECT * FROM visitas WHERE id = ?');
    $stmtSalida->execute([$visitaBD['id']]);
    $visitaFinalizadaBD = $stmtSalida->fetch(PDO::FETCH_ASSOC);
    verify($visitaFinalizadaBD['estado'] === 'Finalizada', 'Estado de la visita actualizado a "Finalizada"');
    verify(! empty($visitaFinalizadaBD['fecha_salida']), 'Fecha y hora de salida registradas');

    // 2.5 Validación: departamento inactivo rechazado
    $csrfInactivo = tokenFrom($conserjeClient, '/conserje/visitas');
    $postInactivo = $conserjeClient->post('/conserje/visitas', [
        'form_params' => [
            '_token' => $csrfInactivo,
            'departamento_id' => $deptoInactivoId,
            'nombre_visitante' => 'Visita No Permitida',
        ],
    ]);
    verify($postInactivo->getStatusCode() === 302, 'Registro de visita a departamento inactivo es rechazado');

    // ==========================================
    // PRUEBAS HU-30: REGISTRO Y CONTROL VEHICULAR
    // ==========================================
    echo "\n-- Verificando HU-30: Registro y Verificación de Vehículos --\n";

    // 3.1 Acceso a la vista de vehículos
    $vistaVehiculos = $conserjeClient->get('/conserje/vehiculos');
    verify($vistaVehiculos->getStatusCode() === 200, 'Conserje accede a /conserje/vehiculos (HTTP 200)');
    $htmlVehiculos = (string) $vistaVehiculos->getBody();
    verify(str_contains($htmlVehiculos, 'Registrar Vehículo Autorizado'), 'Formulario de vehículos visible');
    verify(str_contains($htmlVehiculos, 'Verificador Rápido de Patente'), 'Barra de verificación rápida visible');

    // 3.2 Registro de vehículo con patente estándar chilena (con guion o minúsculas a normalizar)
    $patenteOriginal = "bb-cl.12";
    $patenteEsperada = "BBCL12";
    $csrfVehiculo = tokenFrom($conserjeClient, '/conserje/vehiculos');
    $postVehiculo = $conserjeClient->post('/conserje/vehiculos', [
        'form_params' => [
            '_token' => $csrfVehiculo,
            'departamento_id' => $deptoActivoId,
            'patente' => $patenteOriginal,
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'color' => 'Blanco',
            'tipo' => 'Residente',
            'observaciones' => 'Estacionamiento 22',
        ],
    ]);
    verify($postVehiculo->getStatusCode() === 302, 'Registro de vehículo responde 302 Redirect');

    // Comprobar persistencia y normalización de patente en BD
    $stmtVehiculo = $db->prepare('SELECT * FROM vehiculos_autorizados WHERE departamento_id = ? AND patente = ?');
    $stmtVehiculo->execute([$deptoActivoId, $patenteEsperada]);
    $vehiculoBD = $stmtVehiculo->fetch(PDO::FETCH_ASSOC);
    verify($vehiculoBD !== false, 'Vehículo persistido en la base de datos');
    $createdVehiculoIds[] = (int) $vehiculoBD['id'];

    verify($vehiculoBD['patente'] === 'BBCL12', 'Patente normalizada a mayúsculas y caracteres limpios');
    verify($vehiculoBD['estado'] === 'Autorizado', 'Estado vehicular inicial es "Autorizado"');
    verify((int) $vehiculoBD['conserje_id'] === $conserjeId, 'Conserje registrador asociado');

    // 3.3 Consulta y Verificación de Patente Autorizada (vía JSON y Web)
    $apiVerificar = $conserjeClient->get('/conserje/vehiculos/verificar?patente=BB-CL12', [
        'headers' => ['Accept' => 'application/json'],
    ]);
    verify($apiVerificar->getStatusCode() === 200, 'Endpoint de verificación responde HTTP 200 JSON');
    $dataVerificar = json_decode((string) $apiVerificar->getBody(), true);
    verify($dataVerificar['encontrado'] === true, 'Verificación: patente encontrada');
    verify($dataVerificar['autorizado'] === true, 'Verificación: estado autorizado = true');
    verify($dataVerificar['departamento'] === "801-$suffix", 'Verificación: vinculado al depto correcto');

    // 3.4 Consulta y Verificación de Patente NO Autorizada (no existente)
    $apiNoAutorizada = $conserjeClient->get('/conserje/vehiculos/verificar?patente=XXYY99', [
        'headers' => ['Accept' => 'application/json'],
    ]);
    $dataNoAutorizada = json_decode((string) $apiNoAutorizada->getBody(), true);
    verify($dataNoAutorizada['encontrado'] === false, 'Patente inexistente: encontrado = false');
    verify($dataNoAutorizada['autorizado'] === false, 'Patente inexistente: autorizado = false');

    // 3.5 Cambio de Estado (Revocar / Habilitar)
    $csrfEstado = tokenFrom($conserjeClient, '/conserje/vehiculos');
    $patchEstado = $conserjeClient->patch('/conserje/vehiculos/'.$vehiculoBD['id'].'/estado', [
        'form_params' => [
            '_token' => $csrfEstado,
        ],
    ]);
    verify($patchEstado->getStatusCode() === 302, 'Cambio de estado de vehículo responde 302 Redirect');

    $stmtRevocado = $db->prepare('SELECT estado FROM vehiculos_autorizados WHERE id = ?');
    $stmtRevocado->execute([$vehiculoBD['id']]);
    $estadoActualizado = $stmtRevocado->fetchColumn();
    verify($estadoActualizado === 'Revocado', 'Estado del vehículo cambiado a "Revocado"');

    // Al estar revocado, la verificación debe retornar autorizado = false
    $apiVerificarRevocado = $conserjeClient->get('/conserje/vehiculos/verificar?patente=BBCL12', [
        'headers' => ['Accept' => 'application/json'],
    ]);
    $dataRevocado = json_decode((string) $apiVerificarRevocado->getBody(), true);
    verify($dataRevocado['autorizado'] === false, 'Patente revocada rechazada en control de acceso');

    // ==========================================
    // CONTROL RBAC Y ACCESOS NO AUTORIZADOS
    // ==========================================
    echo "\n-- Verificando Control RBAC --\n";

    $residenteClient = sessionClient();
    login($residenteClient, $residenteEmail);

    $residenteVisitas = $residenteClient->get('/conserje/visitas');
    verify($residenteVisitas->getStatusCode() === 403, 'Residente bloqueado en /conserje/visitas (HTTP 403)');

    $residenteVehiculos = $residenteClient->get('/conserje/vehiculos');
    verify($residenteVehiculos->getStatusCode() === 403, 'Residente bloqueado en /conserje/vehiculos (HTTP 403)');

    $guestClient = sessionClient();
    $guestVisitas = $guestClient->get('/conserje/visitas');
    verify($guestVisitas->getStatusCode() === 302, 'Invitado sin autenticar redirigido al login');

    echo "\n======================================================\n";
    echo "¡TODOS LOS CRITERIOS DE HU-29 Y HU-30 FUERON SATISFECHOS!\n";
    echo "======================================================\n";

} finally {
    // Limpieza de datos de prueba
    if (! empty($createdVisitaIds)) {
        $in = implode(',', array_fill(0, count($createdVisitaIds), '?'));
        $db->prepare("DELETE FROM visitas WHERE id IN ($in)")->execute($createdVisitaIds);
    }
    if (! empty($createdVehiculoIds)) {
        $in = implode(',', array_fill(0, count($createdVehiculoIds), '?'));
        $db->prepare("DELETE FROM vehiculos_autorizados WHERE id IN ($in)")->execute($createdVehiculoIds);
    }
    if (! empty($createdDeptoIds)) {
        $in = implode(',', array_fill(0, count($createdDeptoIds), '?'));
        $db->prepare("DELETE FROM departamentos WHERE id IN ($in)")->execute($createdDeptoIds);
    }
    if (! empty($createdUserIds)) {
        $in = implode(',', array_fill(0, count($createdUserIds), '?'));
        $db->prepare("DELETE FROM users WHERE id IN ($in)")->execute($createdUserIds);
    }
}
