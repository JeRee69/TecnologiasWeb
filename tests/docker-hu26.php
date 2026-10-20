<?php

// Integración HU-26: Consulta de encomiendas pendientes asociadas al departamento del residente
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

function login(Client $client, string $email, string $password = 'PruebaHU26!123'): \Psr\Http\Message\ResponseInterface
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
    // 1. Crear 2 residentes: Residente A (Depto A) y Residente B (Depto B)
    $residenteAEmail = "residente-a-$suffix@example.test";
    $insertUser = $db->prepare('INSERT INTO users (nombre, email, password, rol_id, estado, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
    $insertUser->execute(['Residente Ana', $residenteAEmail, password_hash('PruebaHU26!123', PASSWORD_BCRYPT), $roles['Residente'], 'activo']);
    $createdUserIds[] = $residenteAId = (int) $db->lastInsertId();

    $residenteBEmail = "residente-b-$suffix@example.test";
    $insertUser->execute(['Residente Bruno', $residenteBEmail, password_hash('PruebaHU26!123', PASSWORD_BCRYPT), $roles['Residente'], 'activo']);
    $createdUserIds[] = $residenteBId = (int) $db->lastInsertId();

    $conserjeEmail = "conserje-hu26-$suffix@example.test";
    $insertUser->execute(['Conserje HU26', $conserjeEmail, password_hash('PruebaHU26!123', PASSWORD_BCRYPT), $roles['Conserje'], 'activo']);
    $createdUserIds[] = $conserjeId = (int) $db->lastInsertId();

    // 2. Crear Departamentos y asociar cada residente a su respectivo departamento
    $insertDepto = $db->prepare('INSERT INTO departamentos (numero, piso, estado, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())');
    $insertDepto->execute(["301-$suffix", 3, 'activo']);
    $createdDeptoIds[] = $deptoAId = (int) $db->lastInsertId();

    $insertDepto->execute(["302-$suffix", 3, 'activo']);
    $createdDeptoIds[] = $deptoBId = (int) $db->lastInsertId();

    $insertPivot = $db->prepare('INSERT INTO departamento_user (departamento_id, user_id, es_titular, created_at, updated_at) VALUES (?, ?, 1, NOW(), NOW())');
    $insertPivot->execute([$deptoAId, $residenteAId]);
    $insertPivot->execute([$deptoBId, $residenteBId]);

    // 3. Crear encomiendas:
    // - Encomienda 1: Para Depto A, estado 'Ingresado', empresa 'Chilexpress', fecha 2026-10-19 14:00
    // - Encomienda 2: Para Depto A, estado 'En Custodia', empresa 'Starken', fecha 2026-10-19 16:30
    // - Encomienda 3: Para Depto A, estado 'Retirado', empresa 'Mercado Libre' (ya entregada, NO debe mostrarse como pendiente)
    // - Encomienda 4: Para Depto B, estado 'Ingresado', empresa 'Correos de Chile' (Depto B, NO debe verla Residente A)
    $insertEnc = $db->prepare('INSERT INTO encomiendas (departamento_id, conserje_id, empresa_delivery, fecha_ingreso, estado, codigo_seguimiento, descripcion, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
    
    $insertEnc->execute([$deptoAId, $conserjeId, 'Chilexpress', '2026-10-19 14:00:00', 'Ingresado', 'CHX-A1', 'Caja pequeña']);
    $createdEncomiendaIds[] = (int) $db->lastInsertId();

    $insertEnc->execute([$deptoAId, $conserjeId, 'Starken', '2026-10-19 16:30:00', 'En Custodia', 'STK-A2', 'Sobre con documentos']);
    $createdEncomiendaIds[] = (int) $db->lastInsertId();

    $insertEnc->execute([$deptoAId, $conserjeId, 'Mercado Libre', '2026-10-18 10:00:00', 'Retirado', 'MELI-A3', 'Entregado al residente']);
    $createdEncomiendaIds[] = (int) $db->lastInsertId();

    $insertEnc->execute([$deptoBId, $conserjeId, 'Correos de Chile', '2026-10-19 17:00:00', 'Ingresado', 'CCH-B1', 'Paquete para Depto B']);
    $createdEncomiendaIds[] = (int) $db->lastInsertId();

    // 4. Residente A inicia sesión
    $clientA = sessionClient();
    $loginRes = login($clientA, $residenteAEmail);
    verify($loginRes->getStatusCode() === 302, 'Residente A inicia sesión');

    // 5. Residente A accede a /residente/encomiendas
    $vistaA = $clientA->get('/residente/encomiendas');
    verify($vistaA->getStatusCode() === 200, 'Residente A accede a /residente/encomiendas (HTTP 200)');
    $htmlA = (string) $vistaA->getBody();

    // 6. Verificar visualización de encomiendas pendientes asociadas a su departamento
    verify(str_contains($htmlA, "Depto. 301-$suffix"), 'Visualiza su departamento asignado (301)');
    verify(str_contains($htmlA, 'Chilexpress'), 'Visualiza empresa de delivery de su encomienda 1 (Chilexpress)');
    verify(str_contains($htmlA, '14:00 hrs') || str_contains($htmlA, '14:00'), 'Visualiza hora de recepción de encomienda 1');
    verify(str_contains($htmlA, 'CHX-A1'), 'Visualiza código de encomienda 1');

    verify(str_contains($htmlA, 'Starken'), 'Visualiza empresa de delivery de su encomienda 2 (Starken)');
    verify(str_contains($htmlA, '16:30 hrs') || str_contains($htmlA, '16:30'), 'Visualiza hora de recepción de encomienda 2');
    verify(str_contains($htmlA, 'STK-A2'), 'Visualiza código de encomienda 2');

    // 7. Criterio de Aislamiento: Residente A NO debe visualizar encomiendas de otros departamentos
    verify(! str_contains($htmlA, "Depto. 302-$suffix"), 'No visualiza el departamento de otro vecino (302)');
    verify(! str_contains($htmlA, 'Correos de Chile'), 'No visualiza empresa de delivery del Depto B (Correos de Chile)');
    verify(! str_contains($htmlA, 'CCH-B1'), 'No visualiza el código del paquete de otro departamento');

    // 8. Criterio de Pendientes: Encomiendas con estado 'Retirado' NO deben mostrarse
    verify(! str_contains($htmlA, 'MELI-A3'), 'Encomienda retirada (MELI-A3) no se muestra en pendientes');

    // 9. Residente B inicia sesión y visualiza únicamente sus encomiendas
    $clientB = sessionClient();
    login($clientB, $residenteBEmail);
    $vistaB = $clientB->get('/residente/encomiendas');
    verify($vistaB->getStatusCode() === 200, 'Residente B accede a /residente/encomiendas (HTTP 200)');
    $htmlB = (string) $vistaB->getBody();

    verify(str_contains($htmlB, "Depto. 302-$suffix"), 'Residente B visualiza su propio departamento (302)');
    verify(str_contains($htmlB, 'Correos de Chile'), 'Residente B visualiza su encomienda (Correos de Chile)');
    verify(str_contains($htmlB, 'CCH-B1'), 'Residente B visualiza su código');
    verify(! str_contains($htmlB, 'Chilexpress'), 'Residente B no visualiza encomiendas del Depto A');
    verify(! str_contains($htmlB, 'Starken'), 'Residente B no visualiza encomiendas de otros');

    // 10. Control RBAC: Conserje intentando ingresar a /residente/encomiendas es bloqueado (HTTP 403)
    $clientConserje = sessionClient();
    login($clientConserje, $conserjeEmail);
    $conserjeGet = $clientConserje->get('/residente/encomiendas');
    verify($conserjeGet->getStatusCode() === 403, 'Conserje no puede acceder a /residente/encomiendas (HTTP 403)');

    // 11. Visitante no autenticado es redirigido a login
    $guestClient = sessionClient();
    $guestGet = $guestClient->get('/residente/encomiendas');
    verify($guestGet->getStatusCode() === 302, 'Invitado sin autenticación es redirigido');

    echo "HU-26: criterios de aceptación verificados exitosamente.\n";

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
