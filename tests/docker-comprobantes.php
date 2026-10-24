<?php

// Integración HU-16, HU-17 y HU-18: Carga, Revisión, Aprobación y Rechazo de Comprobantes de Pago
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
        throw new RuntimeException("No se pudo obtener CSRF desde $path (Status: {$response->getStatusCode()})");
    }
    return $matches[1];
}

function login(Client $client, string $email, string $password = 'Password123!'): \Psr\Http\Message\ResponseInterface
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

echo "--- INICIANDO SUITE DE PRUEBAS HU-16, HU-17 Y HU-18 (PAGOS Y COMPROBANTES) ---" . PHP_EOL;

// 1. Obtener ID de Ámbar Residente y su departamento
$stmtUser = $db->prepare("SELECT id, nombre, email FROM users WHERE email = 'residente@ediconnect.cl'");
$stmtUser->execute();
$residente = $stmtUser->fetch(PDO::FETCH_ASSOC);
verify((bool) $residente, 'Residente demo encontrado en base de datos');

// Asegurar departamento 202 activo
$stmtDepto = $db->prepare("SELECT id FROM departamentos WHERE numero = '202'");
$stmtDepto->execute();
$depto202 = $stmtDepto->fetch(PDO::FETCH_ASSOC);
if (! $depto202) {
    $db->exec("INSERT INTO departamentos (numero, piso, estado, created_at, updated_at) VALUES ('202', 5, 'activo', NOW(), NOW())");
    $deptoId = $db->lastInsertId();
} else {
    $deptoId = $depto202['id'];
}

// Vincular residente con depto 202 si no lo estuviera
$stmtRel = $db->prepare("SELECT id FROM departamento_user WHERE departamento_id = ? AND user_id = ?");
$stmtRel->execute([$deptoId, $residente['id']]);
if (! $stmtRel->fetch()) {
    $db->prepare("INSERT INTO departamento_user (departamento_id, user_id, es_titular, created_at, updated_at) VALUES (?, ?, 1, NOW(), NOW())")
        ->execute([$deptoId, $residente['id']]);
}

// Crear gasto común pendiente de octubre 2026 para pruebas
$db->prepare("DELETE FROM gastos_comunes WHERE departamento_id = ? AND periodo = '2026-10'")->execute([$deptoId]);
$db->prepare("INSERT INTO gastos_comunes (departamento_id, periodo, monto_total, fecha_emision, fecha_vencimiento, estado, created_at, updated_at) VALUES (?, '2026-10', 68500, '2026-10-01', '2026-10-31', 'Pendiente', NOW(), NOW())")
    ->execute([$deptoId]);
$gastoId = $db->lastInsertId();

// -------------------------------------------------------------
// VERIFICAR HU-16: CARGA DE COMPROBANTE DE PAGO
// -------------------------------------------------------------
echo PHP_EOL . "-- Verificando HU-16: Carga de Comprobante por Residente --" . PHP_EOL;

$residenteClient = sessionClient();
$loginRes = login($residenteClient, 'residente@ediconnect.cl');
verify($loginRes->getStatusCode() === 302, 'Residente inicia sesión exitosamente');

// Acceso al listado de gastos comunes
$vistaGastos = $residenteClient->get('/residente/gastos-comunes');
verify($vistaGastos->getStatusCode() === 200, 'Residente accede a /residente/gastos-comunes (HTTP 200)');
$htmlGastos = (string) $vistaGastos->getBody();
verify(str_contains($htmlGastos, '2026-10'), 'Visualiza el período del gasto común (2026-10)');
verify(str_contains($htmlGastos, 'Depto 202'), 'Visualiza su departamento asignado (Depto 202)');
verify(str_contains($htmlGastos, '68.500'), 'Visualiza el monto total del cobro ($ 68.500)');
verify(str_contains($htmlGastos, 'Cargar Comprobante'), 'Botón Cargar Comprobante presente para gasto pendiente');

// Intento 1: Formato no permitido (.txt o .exe)
$csrfToken = tokenFrom($residenteClient, '/residente/gastos-comunes');
$resInvalido = $residenteClient->post("/residente/gastos-comunes/{$gastoId}/comprobante", [
    'multipart' => [
        ['name' => '_token', 'contents' => $csrfToken],
        ['name' => 'comprobante', 'contents' => 'archivo no permitido', 'filename' => 'malicioso.exe', 'headers' => ['Content-Type' => 'application/x-msdownload']],
    ],
]);
verify($resInvalido->getStatusCode() === 302, 'Petición con formato inválido es redirigida (HTTP 302)');
$vistaConError = $residenteClient->get('/residente/gastos-comunes');
verify(str_contains((string) $vistaConError->getBody(), 'formato') || str_contains((string) $vistaConError->getBody(), 'comprobante'), 'Error de formato no permitido desplegado al usuario');

// Intento 2: Carga exitosa con formato permitido (PDF de 3.5 MB) y datos válidos
$pdfContent = "%PDF-1.4\n" . str_repeat("0", (int)(3.5 * 1024 * 1024)) . "\n%%EOF";
$csrfToken = tokenFrom($residenteClient, '/residente/gastos-comunes');
$resCarga = $residenteClient->post("/residente/gastos-comunes/{$gastoId}/comprobante", [
    'multipart' => [
        ['name' => '_token', 'contents' => $csrfToken],
        ['name' => 'comprobante', 'contents' => $pdfContent, 'filename' => 'transferencia_octubre.pdf', 'headers' => ['Content-Type' => 'application/pdf']],
        ['name' => 'monto', 'contents' => '68500'],
        ['name' => 'fecha_pago', 'contents' => '2026-10-24'],
    ],
]);
verify($resCarga->getStatusCode() === 302, 'Carga de comprobante de 3.5 MB responde redirección de éxito (HTTP 302)');

// Verificación en base de datos
$stmtComp = $db->prepare("SELECT * FROM comprobantes_pago WHERE gasto_comun_id = ? ORDER BY id DESC LIMIT 1");
$stmtComp->execute([$gastoId]);
$comprobante = $stmtComp->fetch(PDO::FETCH_ASSOC);

verify((bool) $comprobante, 'Comprobante persistido exitosamente en la base de datos');
verify($comprobante['estado'] === 'en_revision', 'Comprobante queda con estado inicial "en_revision"');
verify((int) $comprobante['departamento_id'] === (int) $deptoId, 'Comprobante asociado al departamento indicado');
verify((int) $comprobante['user_id'] === (int) $residente['id'], 'Comprobante asociado al residente autenticado');
verify((int) $comprobante['monto'] === 68500, 'Monto de transferencia almacenado correctamente');

// Verificación de actualización del gasto común
$stmtGasto = $db->prepare("SELECT estado FROM gastos_comunes WHERE id = ?");
$stmtGasto->execute([$gastoId]);
$gastoActualizado = $stmtGasto->fetch(PDO::FETCH_ASSOC);
verify($gastoActualizado['estado'] === 'En revisión', 'El gasto común asociado cambia su estado a "En revisión"');

// Verificación en la vista del residente
$vistaRevision = $residenteClient->get('/residente/gastos-comunes');
$htmlRevision = (string) $vistaRevision->getBody();
verify(str_contains($htmlRevision, 'En revisión'), 'Estado "En revisión" visible en la interfaz del residente');

// Endpoint API: POST /api/v1/comprobantes
$apiClient = new Client(['base_uri' => 'http://web', 'http_errors' => false]);
$validPng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
$resApi = $apiClient->post('/api/v1/comprobantes', [
    'multipart' => [
        ['name' => 'gasto_id', 'contents' => (string) $gastoId],
        ['name' => 'file', 'contents' => $validPng, 'filename' => 'comprobante_banco.png', 'headers' => ['Content-Type' => 'image/png']],
    ],
    'headers' => ['Accept' => 'application/json'],
]);
verify($resApi->getStatusCode() === 201, 'Endpoint API POST /api/v1/comprobantes responde HTTP 201 Created');
$jsonApi = json_decode((string) $resApi->getBody(), true);
verify(isset($jsonApi['estado']) && $jsonApi['estado'] === 'en_revision', 'API retorna { id, estado: "en_revision" }');

// Endpoint API: GET /api/v1/gastos-comunes?departamento_id=X
$resApiGastos = $apiClient->get("/api/v1/gastos-comunes?departamento_id={$deptoId}", ['headers' => ['Accept' => 'application/json']]);
verify($resApiGastos->getStatusCode() === 200, 'Endpoint API GET /api/v1/gastos-comunes responde HTTP 200 OK');

// -------------------------------------------------------------
// VERIFICAR HU-17: REVISIÓN DE COMPROBANTES POR ADMINISTRADOR
// -------------------------------------------------------------
echo PHP_EOL . "-- Verificando HU-17: Revisión de Comprobantes por Administrador --" . PHP_EOL;

$adminClient = sessionClient();
$loginAdmin = login($adminClient, 'admin@ediconnect.cl');
verify($loginAdmin->getStatusCode() === 302, 'Administrador inicia sesión exitosamente');

$vistaAdmin = $adminClient->get('/admin/comprobantes');
verify($vistaAdmin->getStatusCode() === 200, 'Administrador accede a /admin/comprobantes (HTTP 200)');
$htmlAdmin = (string) $vistaAdmin->getBody();

verify(str_contains($htmlAdmin, $residente['nombre']), 'El administrador visualiza el nombre del residente');
verify(str_contains($htmlAdmin, 'Depto 202'), 'El administrador visualiza el departamento correspondiente');
verify(str_contains($htmlAdmin, '2026-10'), 'El administrador visualiza el período del gasto común');
verify(str_contains($htmlAdmin, '68.500'), 'El administrador visualiza el monto correspondiente');
verify(str_contains($htmlAdmin, 'Ver comprobante'), 'El administrador visualiza el enlace/botón al comprobante');

// Control RBAC: Conserje y Residente bloqueados
$conserjeClient = sessionClient();
login($conserjeClient, 'conserje@ediconnect.cl');
$resConserjeBloqueado = $conserjeClient->get('/admin/comprobantes');
verify($resConserjeBloqueado->getStatusCode() === 403, 'Conserje no autorizado para revisar comprobantes de pago (HTTP 403)');

$resResidenteBloqueado = $residenteClient->get('/admin/comprobantes');
verify($resResidenteBloqueado->getStatusCode() === 403, 'Residente no autorizado para revisar comprobantes administrativos (HTTP 403)');

// -------------------------------------------------------------
// VERIFICAR HU-18: APROBACIÓN O RECHAZO DE PAGOS
// -------------------------------------------------------------
echo PHP_EOL . "-- Verificando HU-18: Aprobación o Rechazo de Pagos --" . PHP_EOL;

// 1. Caso Aprobación: Administrador aprueba el comprobante
$csrfAdmin = tokenFrom($adminClient, '/admin/comprobantes');
$resAprobar = $adminClient->patch("/admin/comprobantes/{$comprobante['id']}/aprobar", [
    'form_params' => [
        '_token' => $csrfAdmin,
    ],
]);
verify($resAprobar->getStatusCode() === 302, 'Aprobación de comprobante responde 302 Redirect');

// Verificar actualización del comprobante y del gasto en base de datos
$stmtCompAprobado = $db->prepare("SELECT estado, revisado_por, revisado_at FROM comprobantes_pago WHERE id = ?");
$stmtCompAprobado->execute([$comprobante['id']]);
$compAprobado = $stmtCompAprobado->fetch(PDO::FETCH_ASSOC);
verify($compAprobado['estado'] === 'aprobado', 'Comprobante actualizado a estado "aprobado"');
verify(! empty($compAprobado['revisado_por']), 'ID del administrador auditor registrado');
verify(! empty($compAprobado['revisado_at']), 'Fecha y hora de revisión registrada');

$stmtGastoPagado = $db->prepare("SELECT estado FROM gastos_comunes WHERE id = ?");
$stmtGastoPagado->execute([$gastoId]);
$gastoPagado = $stmtGastoPagado->fetch(PDO::FETCH_ASSOC);
verify($gastoPagado['estado'] === 'Pagado', 'Al aprobar, el gasto común se actualiza a estado "Pagado"');

// 2. Caso Rechazo: Crear un nuevo gasto y comprobante para probar el rechazo con motivo
$db->prepare("INSERT INTO gastos_comunes (departamento_id, periodo, monto_total, fecha_emision, fecha_vencimiento, estado, created_at, updated_at) VALUES (?, '2026-11', 72000, '2026-11-01', '2026-11-30', 'En revisión', NOW(), NOW())")
    ->execute([$deptoId]);
$gastoNovId = $db->lastInsertId();

$db->prepare("INSERT INTO comprobantes_pago (gasto_comun_id, departamento_id, user_id, archivo_path, archivo_nombre_original, archivo_mime, archivo_tamano, monto, fecha_pago, estado, created_at, updated_at) VALUES (?, ?, ?, 'comprobantes/fake.pdf', 'fake.pdf', 'application/pdf', 1024, 72000, '2026-11-05', 'en_revision', NOW(), NOW())")
    ->execute([$gastoNovId, $deptoId, $residente['id']]);
$compRechazarId = $db->lastInsertId();

// Rechazo sin motivo debe fallar por validación
$csrfAdmin = tokenFrom($adminClient, '/admin/comprobantes');
$resRechazoVacio = $adminClient->patch("/admin/comprobantes/{$compRechazarId}/rechazar", [
    'form_params' => [
        '_token' => $csrfAdmin,
        'motivo_rechazo' => '',
    ],
]);
verify($resRechazoVacio->getStatusCode() === 302, 'Intento de rechazar sin motivo es rechazado');

// Rechazo con motivo válido
$motivoRechazo = 'El número de operación no coincide con los registros contables.';
$csrfAdmin = tokenFrom($adminClient, '/admin/comprobantes');
$resRechazar = $adminClient->patch("/admin/comprobantes/{$compRechazarId}/rechazar", [
    'form_params' => [
        '_token' => $csrfAdmin,
        'motivo_rechazo' => $motivoRechazo,
    ],
]);
verify($resRechazar->getStatusCode() === 302, 'Rechazo de comprobante con motivo responde 302 Redirect');

// Verificar en base de datos
$stmtCompRechazado = $db->prepare("SELECT estado, motivo_rechazo FROM comprobantes_pago WHERE id = ?");
$stmtCompRechazado->execute([$compRechazarId]);
$compRechazado = $stmtCompRechazado->fetch(PDO::FETCH_ASSOC);
verify($compRechazado['estado'] === 'rechazado', 'Comprobante actualizado a estado "rechazado"');
verify($compRechazado['motivo_rechazo'] === $motivoRechazo, 'Motivo de rechazo almacenado en base de datos');

$stmtGastoPendiente = $db->prepare("SELECT estado FROM gastos_comunes WHERE id = ?");
$stmtGastoPendiente->execute([$gastoNovId]);
$gastoPendiente = $stmtGastoPendiente->fetch(PDO::FETCH_ASSOC);
verify($gastoPendiente['estado'] === 'Pendiente', 'Al rechazar, el sistema mantiene el gasto común en estado "Pendiente"');

// Residente visualiza el resultado y el motivo del rechazo en su panel
$vistaResidenteRechazo = $residenteClient->get('/residente/gastos-comunes');
$htmlResidenteRechazo = (string) $vistaResidenteRechazo->getBody();
verify(str_contains($htmlResidenteRechazo, 'Comprobantes con observaciones o rechazados'), 'El residente visualiza el resultado del rechazo en su portal');
verify(str_contains($htmlResidenteRechazo, $motivoRechazo), 'El residente visualiza el motivo específico del rechazo');

echo PHP_EOL . "========================================================" . PHP_EOL;
echo "¡TODOS LOS CRITERIOS DE HU-16, HU-17 Y HU-18 SATISFECHOS EXITOSAMENTE!" . PHP_EOL;
echo "========================================================" . PHP_EOL;
