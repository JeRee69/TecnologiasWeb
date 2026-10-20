<?php

// Integración HU-27: Registro del retiro de una encomienda por parte de conserjería
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

function login(Client $client, string $email, string $password = 'PruebaHU27!123'): \Psr\Http\Message\ResponseInterface
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
    // 1. Crear Conserje y Residente
    $conserjeEmail = "conserje-hu27-$suffix@example.test";
    $insertUser = $db->prepare('INSERT INTO users (nombre, email, password, rol_id, estado, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
    $insertUser->execute(['Conserje HU27', $conserjeEmail, password_hash('PruebaHU27!123', PASSWORD_BCRYPT), $roles['Conserje'], 'activo']);
    $createdUserIds[] = $conserjeId = (int) $db->lastInsertId();

    $residenteEmail = "residente-hu27-$suffix@example.test";
    $insertUser->execute(['Residente HU27', $residenteEmail, password_hash('PruebaHU27!123', PASSWORD_BCRYPT), $roles['Residente'], 'activo']);
    $createdUserIds[] = $residenteId = (int) $db->lastInsertId();

    // 2. Crear Departamento y asociar al residente
    $insertDepto = $db->prepare('INSERT INTO departamentos (numero, piso, estado, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())');
    $insertDepto->execute(["401-$suffix", 4, 'activo']);
    $createdDeptoIds[] = $deptoId = (int) $db->lastInsertId();

    $insertPivot = $db->prepare('INSERT INTO departamento_user (departamento_id, user_id, es_titular, created_at, updated_at) VALUES (?, ?, 1, NOW(), NOW())');
    $insertPivot->execute([$deptoId, $residenteId]);

    // 3. Crear una encomienda pendiente para el departamento
    $insertEnc = $db->prepare('INSERT INTO encomiendas (departamento_id, conserje_id, empresa_delivery, fecha_ingreso, estado, codigo_seguimiento, descripcion, created_at, updated_at) VALUES (?, ?, ?, NOW(), "Ingresado", ?, ?, NOW(), NOW())');
    $insertEnc->execute([$deptoId, $conserjeId, 'Chilexpress', 'TRACK-HU27-01', 'Sobre con tarjeta bancaria']);
    $createdEncomiendaIds[] = $encomiendaId = (int) $db->lastInsertId();

    // 4. Conserje inicia sesión
    $conserjeClient = sessionClient();
    $loginRes = login($conserjeClient, $conserjeEmail);
    verify($loginRes->getStatusCode() === 302, 'Conserje inicia sesión exitosamente');

    // 5. Conserje visualiza la bitácora con el botón de "Registrar retiro"
    $vistaBitacora = $conserjeClient->get('/conserje/encomiendas');
    verify($vistaBitacora->getStatusCode() === 200, 'Conserje accede a /conserje/encomiendas');
    $htmlBitacora = (string) $vistaBitacora->getBody();
    verify(str_contains($htmlBitacora, 'TRACK-HU27-01'), 'Encomienda visible en la bitácora');
    verify(str_contains($htmlBitacora, 'Registrar retiro'), 'Botón "Registrar retiro" presente para encomienda pendiente');

    // 6. Conserje registra el retiro de la encomienda (HU-27)
    $csrf = tokenFrom($conserjeClient, '/conserje/encomiendas');
    $tiempoAntes = time();
    $patchRetiro = $conserjeClient->patch("/conserje/encomiendas/{$encomiendaId}/retiro", [
        'form_params' => [
            '_token' => $csrf,
        ],
    ]);
    verify($patchRetiro->getStatusCode() === 302, 'Petición PATCH de retiro responde 302 tras procesar');

    // 7. Criterios de Aceptación:
    // "Al registrar el retiro, el sistema cambia el estado de la encomienda a “Retirado” y almacena la fecha y hora del retiro."
    $stmt = $db->prepare('SELECT id, estado, fecha_retiro FROM encomiendas WHERE id = ?');
    $stmt->execute([$encomiendaId]);
    $encomiendaActualizada = $stmt->fetch(PDO::FETCH_ASSOC);

    verify($encomiendaActualizada !== false, 'Encomienda encontrada en base de datos');
    verify($encomiendaActualizada['estado'] === 'Retirado', 'El sistema cambia el estado de la encomienda a "Retirado"');
    verify(! empty($encomiendaActualizada['fecha_retiro']), 'El sistema almacena la fecha y hora del retiro');
    verify(preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $encomiendaActualizada['fecha_retiro']) === 1, 'Formato válido de fecha y hora del retiro');

    // 8. En la bitácora de conserjería se muestra el estado "Retirado" y la fecha de entrega
    $vistaActualizada = $conserjeClient->get('/conserje/encomiendas');
    $htmlActualizada = (string) $vistaActualizada->getBody();
    verify(str_contains($htmlActualizada, 'Retirado'), 'Estado Retirado reflejado en la vista');
    verify(str_contains($htmlActualizada, 'Entregado'), 'Indicador de entrega presente');

    // 9. Intento de retirar nuevamente la misma encomienda debe ser rechazado
    $csrfDuplicado = tokenFrom($conserjeClient, '/conserje/encomiendas');
    $patchDuplicado = $conserjeClient->patch("/conserje/encomiendas/{$encomiendaId}/retiro", [
        'form_params' => [
            '_token' => $csrfDuplicado,
        ],
    ]);
    verify($patchDuplicado->getStatusCode() === 302, 'Intento de retirar encomienda ya retirada redirige');
    $vistaPostDuplicado = $conserjeClient->get('/conserje/encomiendas');
    verify(str_contains((string) $vistaPostDuplicado->getBody(), 'Esta encomienda ya fue retirada anteriormente'), 'Mensaje de validación ante retiro duplicado');

    // 10. Comprobar coherencia con HU-26: La encomienda ya NO debe aparecerle al residente en pendientes
    $residenteClient = sessionClient();
    login($residenteClient, $residenteEmail);
    $vistaResidente = $residenteClient->get('/residente/encomiendas');
    verify($vistaResidente->getStatusCode() === 200, 'Residente accede a su listado de encomiendas');
    $htmlResidente = (string) $vistaResidente->getBody();
    verify(! str_contains($htmlResidente, 'TRACK-HU27-01'), 'La encomienda retirada no se muestra en pendientes del residente');
    verify(str_contains($htmlResidente, 'No tienes encomiendas pendientes'), 'Residente visualiza estado vacío de pendientes');

    // 11. Control RBAC: Residente no puede registrar retiros de encomiendas
    $csrfResidente = tokenFrom($residenteClient, '/residente/dashboard');
    $patchDesdeResidente = $residenteClient->patch("/conserje/encomiendas/{$encomiendaId}/retiro", [
        'form_params' => [
            '_token' => $csrfResidente,
        ],
    ]);
    verify($patchDesdeResidente->getStatusCode() === 403, 'Residente no autorizado para registrar retiros (HTTP 403)');

    // 12. Visitante no autenticado es redirigido al login
    $guestClient = sessionClient();
    $patchGuest = $guestClient->patch("/conserje/encomiendas/{$encomiendaId}/retiro", [
        'form_params' => [
            '_token' => 'token-invalido',
        ],
    ]);
    verify($patchGuest->getStatusCode() === 302 || $patchGuest->getStatusCode() === 419, 'Visitante no autenticado es rechazado');

    echo "HU-27: criterios de aceptación verificados exitosamente.\n";

} finally {
    if (! empty($createdEncomiendaIds)) {
        $placeholders = implode(',', array_fill(0, count($createdEncomiendaIds), '?'));
        $db->prepare("DELETE FROM encomiendas WHERE id IN ($placeholders)")->execute($createdEncomiendaIds);
    }
    if (! empty($createdDeptoIds)) {
        $placeholders = implode(',', array_fill(0, count($createdDeptoIds), '?'));
        $db->prepare("DELETE FROM departamento_user WHERE departamento_id IN ($placeholders)")->execute($createdDeptoIds);
        $db->prepare("DELETE FROM departamentos WHERE id IN ($placeholders)")->execute($createdDeptoIds);
    }
    if (! empty($createdUserIds)) {
        $placeholders = implode(',', array_fill(0, count($createdUserIds), '?'));
        $db->prepare("DELETE FROM users WHERE id IN ($placeholders)")->execute($createdUserIds);
    }
}
