<?php

// Integración Sprint 4: Mural Informativo (HU-31, HU-32, HU-33) e Incidencias (HU-34, HU-35, HU-36, HU-37)
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

function sessionClient(string $baseUri = 'http://web'): Client
{
    return new Client([
        'base_uri' => $baseUri,
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

function login(Client $client, string $email, string $password = 'PruebaSprint4!123'): \Psr\Http\Message\ResponseInterface
{
    return $client->post('/login', ['form_params' => [
        '_token' => tokenFrom($client, '/login'),
        'identifier' => $email,
        'password' => $password,
        'remember' => '1',
    ]]);
}

$baseUri = getenv('TEST_APP_URL') ?: 'http://web';
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbName = getenv('DB_DATABASE') ?: 'ediconnect_db';
$dbUser = getenv('DB_USERNAME') ?: 'ediconnect_user';
$dbPass = getenv('DB_PASSWORD') ?: 'secret';

$db = new PDO(
    "mysql:host=$dbHost;dbname=$dbName",
    $dbUser,
    $dbPass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$roles = $db->query('SELECT nombre, id FROM roles')->fetchAll(PDO::FETCH_KEY_PAIR);
$suffix = bin2hex(random_bytes(6));
$createdUserIds = [];
$createdDeptoIds = [];
$createdAvisoIds = [];
$createdIncidenciaIds = [];

try {
    echo "=========================================================================\n";
    echo "  INICIANDO SUITE DE PRUEBAS AUTOMATIZADAS SPRINT 4 (EDICONNECT)\n";
    echo "  Módulos: Mural Informativo (Avisos) e Incidencias/Reclamos\n";
    echo "=========================================================================\n\n";

    // -------------------------------------------------------------------------
    // 0. Configuración de usuarios y departamentos de prueba
    // -------------------------------------------------------------------------
    $adminEmail = "admin-sprint4-$suffix@example.test";
    $conserjeEmail = "conserje-sprint4-$suffix@example.test";
    $residenteAEmail = "residenteA-sprint4-$suffix@example.test";
    $residenteBEmail = "residenteB-sprint4-$suffix@example.test";
    $passwordHash = password_hash('PruebaSprint4!123', PASSWORD_BCRYPT);

    $insertUser = $db->prepare('INSERT INTO users (nombre, email, password, rol_id, estado, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
    
    // Admin
    $insertUser->execute(['Admin Sprint4', $adminEmail, $passwordHash, $roles['Administrador'], 'activo']);
    $createdUserIds[] = $adminId = (int) $db->lastInsertId();

    // Conserje
    $insertUser->execute(['Conserje Sprint4', $conserjeEmail, $passwordHash, $roles['Conserje'], 'activo']);
    $createdUserIds[] = $conserjeId = (int) $db->lastInsertId();

    // Residente A
    $insertUser->execute(['Residente Ana', $residenteAEmail, $passwordHash, $roles['Residente'], 'activo']);
    $createdUserIds[] = $residenteAId = (int) $db->lastInsertId();

    // Residente B
    $insertUser->execute(['Residente Bernardo', $residenteBEmail, $passwordHash, $roles['Residente'], 'activo']);
    $createdUserIds[] = $residenteBId = (int) $db->lastInsertId();

    // Departamentos de prueba
    $insertDepto = $db->prepare('INSERT INTO departamentos (numero, piso, estado, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())');
    $insertDepto->execute(["401-$suffix", 4, 'activo']);
    $createdDeptoIds[] = $deptoAId = (int) $db->lastInsertId();

    $insertDepto->execute(["402-$suffix", 4, 'activo']);
    $createdDeptoIds[] = $deptoBId = (int) $db->lastInsertId();

    // Asignar departamento a los residentes en departamento_user
    $insertPivot = $db->prepare('INSERT INTO departamento_user (departamento_id, user_id, es_titular, created_at, updated_at) VALUES (?, ?, 1, NOW(), NOW())');
    $insertPivot->execute([$deptoAId, $residenteAId]);
    $insertPivot->execute([$deptoBId, $residenteBId]);

    echo "0. Configuración de usuarios y datos de prueba completada.\n\n";

    // =========================================================================
    // SUITE 1: HU-31 & HU-32 - Publicación, Edición y Retiro de Avisos (Admin)
    // =========================================================================
    echo "--- SUITE 1: HU-31 & HU-32 - Gestión de Avisos por Administrador ---\n";
    $adminClient = sessionClient($baseUri);
    $loginRes = login($adminClient, $adminEmail);
    verify($loginRes->getStatusCode() === 302, 'Admin inicia sesión correctamente');

    // 1.1 Acceso al panel de avisos
    $resAvisos = $adminClient->get('/admin/avisos');
    verify($resAvisos->getStatusCode() === 200, 'Admin accede a /admin/avisos (HTTP 200)');
    verify(str_contains((string) $resAvisos->getBody(), 'Publicar Nuevo Aviso'), 'Formulario de publicación de avisos presente en vista');

    // 1.2 HU-31: Publicación de aviso
    $tituloAviso1 = "Mantenimiento Preventivo de Bombas $suffix";
    $contenidoAviso1 = "Se realizará mantención del sistema hidroneumático de 09:00 a 13:00 hrs.";
    $csrfToken = tokenFrom($adminClient, '/admin/avisos');
    $postAvisoRes = $adminClient->post('/admin/avisos', [
        'form_params' => [
            '_token' => $csrfToken,
            'titulo' => $tituloAviso1,
            'contenido' => $contenidoAviso1,
            'prioridad' => 'Alta',
            'estado' => 'Publicado',
        ],
    ]);
    verify($postAvisoRes->getStatusCode() === 302, 'Aviso publicado correctamente (Redirect 302)');

    // Verificar en BD
    $stmtAviso = $db->prepare('SELECT id, titulo, prioridad, estado, autor_id FROM avisos WHERE titulo = ?');
    $stmtAviso->execute([$tituloAviso1]);
    $aviso1 = $stmtAviso->fetch(PDO::FETCH_ASSOC);
    verify(! empty($aviso1), 'Aviso persistido correctamente en tabla avisos');
    verify($aviso1['prioridad'] === 'Alta', 'Prioridad guardada como Alta');
    verify($aviso1['estado'] === 'Publicado', 'Estado guardado como Publicado');
    verify((int) $aviso1['autor_id'] === $adminId, 'autor_id coincide con el Administrador autenticado');
    $createdAvisoIds[] = $aviso1Id = (int) $aviso1['id'];

    // 1.3 HU-32: Edición del aviso
    $tituloEditado = "Mantenimiento URGENTE de Bombas $suffix";
    $csrfTokenEdit = tokenFrom($adminClient, "/admin/avisos/$aviso1Id/editar");
    $putAvisoRes = $adminClient->put("/admin/avisos/$aviso1Id", [
        'form_params' => [
            '_token' => $csrfTokenEdit,
            'titulo' => $tituloEditado,
            'contenido' => $contenidoAviso1 . ' Actualización con corte inmediato.',
            'prioridad' => 'Urgente',
            'estado' => 'Publicado',
        ],
    ]);
    verify($putAvisoRes->getStatusCode() === 302, 'Aviso editado con redirección 302');

    $stmtAviso->execute([$tituloEditado]);
    $avisoEditado = $stmtAviso->fetch(PDO::FETCH_ASSOC);
    verify($avisoEditado['prioridad'] === 'Urgente', 'Aviso actualizado a prioridad Urgente en BD');

    // 1.4 HU-32: Retiro del aviso
    $csrfTokenEstado = tokenFrom($adminClient, '/admin/avisos');
    $patchAvisoRes = $adminClient->patch("/admin/avisos/$aviso1Id/estado", [
        'form_params' => [
            '_token' => $csrfTokenEstado,
            'estado' => 'Retirado',
        ],
    ]);
    verify($patchAvisoRes->getStatusCode() === 302, 'Aviso retirado correctamente (Redirect 302)');

    $stmtCheck = $db->query("SELECT estado FROM avisos WHERE id = $aviso1Id")->fetch(PDO::FETCH_ASSOC);
    verify($stmtCheck['estado'] === 'Retirado', 'Estado del aviso actualizado a Retirado en BD');

    // Publicar un segundo aviso que permanezca 'Publicado' para probar la vista del residente
    $tituloAvisoVigente = "Asamblea Ordinaria Anual $suffix";
    $csrfToken2 = tokenFrom($adminClient, '/admin/avisos');
    $adminClient->post('/admin/avisos', [
        'form_params' => [
            '_token' => $csrfToken2,
            'titulo' => $tituloAvisoVigente,
            'contenido' => 'Convocatoria a primera y segunda citación para la asamblea de copropietarios.',
            'prioridad' => 'Media',
            'estado' => 'Publicado',
        ],
    ]);
    $stmtAviso->execute([$tituloAvisoVigente]);
    $avisoVigente = $stmtAviso->fetch(PDO::FETCH_ASSOC);
    $createdAvisoIds[] = (int) $avisoVigente['id'];
    verify(! empty($avisoVigente), 'Segundo aviso activo creado para prueba de lectura comunitaria');

    // =========================================================================
    // SUITE 2: HU-33 - Visualización del Mural por Residentes
    // =========================================================================
    echo "\n--- SUITE 2: HU-33 - Visualización del Mural por Residentes ---\n";
    $residenteAClient = sessionClient($baseUri);
    $loginResA = login($residenteAClient, $residenteAEmail);
    verify($loginResA->getStatusCode() === 302, 'Residente A inicia sesión');

    $resMural = $residenteAClient->get('/residente/mural');
    verify($resMural->getStatusCode() === 200, 'Residente A accede a /residente/mural (HTTP 200)');
    $htmlMural = (string) $resMural->getBody();

    verify(str_contains($htmlMural, $tituloAvisoVigente), 'Aviso vigente presente en el mural del residente');
    verify(str_contains($htmlMural, 'Media'), 'Badge de prioridad del aviso vigente visible en el mural');
    verify(! str_contains($htmlMural, $tituloEditado), 'Aviso retirado/inactivo NO es visible en el mural del residente (HU-32 / HU-33)');

    // =========================================================================
    // SUITE 3: HU-34 - Registro de Incidencias por Residentes
    // =========================================================================
    echo "\n--- SUITE 3: HU-34 - Registro de Incidencia por Residente A ---\n";
    $resCreateInc = $residenteAClient->get('/residente/incidencias/crear');
    verify($resCreateInc->getStatusCode() === 200, 'Residente A accede a formulario de creación de incidencia (HTTP 200)');

    $tituloIncidencia = "Falla de Iluminación en Escalera $suffix";
    $descIncidencia = "La lámpara del descanso del piso 4 se encuentra intermitente y genera poca visibilidad.";
    $csrfTokenInc = tokenFrom($residenteAClient, '/residente/incidencias/crear');
    
    $postIncRes = $residenteAClient->post('/residente/incidencias', [
        'form_params' => [
            '_token' => $csrfTokenInc,
            'categoria' => 'Espacios Comunes',
            'titulo' => $tituloIncidencia,
            'descripcion' => $descIncidencia,
            'departamento_id' => $deptoAId,
        ],
    ]);
    verify($postIncRes->getStatusCode() === 302, 'Incidencia enviada correctamente con redirección 302');

    $stmtInc = $db->prepare('SELECT id, user_id, departamento_id, categoria, estado, titulo FROM incidencias WHERE titulo = ?');
    $stmtInc->execute([$tituloIncidencia]);
    $incidenciaA = $stmtInc->fetch(PDO::FETCH_ASSOC);
    verify(! empty($incidenciaA), 'Incidencia persistida en tabla incidencias');
    verify($incidenciaA['estado'] === 'Pendiente', 'Estado inicial asignado automáticamente como Pendiente (HU-34)');
    verify((int) $incidenciaA['user_id'] === $residenteAId, 'user_id asociado correctamente al residente solicitante');
    verify((int) $incidenciaA['departamento_id'] === $deptoAId, 'departamento_id asociado a la unidad del residente');
    $createdIncidenciaIds[] = $incidenciaAId = (int) $incidenciaA['id'];

    // =========================================================================
    // SUITE 4: HU-35 - Seguimiento y Aislamiento Estricto entre Residentes
    // =========================================================================
    echo "\n--- SUITE 4: HU-35 - Seguimiento y Aislamiento Estricto de Datos ---\n";
    // 4.1 Residente A consulta su listado y detalle
    $resListIncA = $residenteAClient->get('/residente/incidencias');
    verify($resListIncA->getStatusCode() === 200, 'Residente A consulta /residente/incidencias (HTTP 200)');
    $htmlListA = (string) $resListIncA->getBody();
    verify(str_contains($htmlListA, $tituloIncidencia), 'Incidencia de Residente A listada en su propio panel');
    verify(str_contains($htmlListA, 'Pendiente'), 'Estado Pendiente visible para Residente A');

    $resShowIncA = $residenteAClient->get("/residente/incidencias/$incidenciaAId");
    verify($resShowIncA->getStatusCode() === 200, 'Residente A consulta el detalle de su propia incidencia (HTTP 200)');

    // 4.2 Residente B inicia sesión y verifica aislamiento
    $residenteBClient = sessionClient($baseUri);
    $loginResB = login($residenteBClient, $residenteBEmail);
    verify($loginResB->getStatusCode() === 302, 'Residente B inicia sesión');

    $resListIncB = $residenteBClient->get('/residente/incidencias');
    verify($resListIncB->getStatusCode() === 200, 'Residente B consulta /residente/incidencias (HTTP 200)');
    $htmlListB = (string) $resListIncB->getBody();
    verify(! str_contains($htmlListB, $tituloIncidencia), 'Incidencia de Residente A NO es visible para Residente B en listado (Aislamiento)');

    // 4.3 Intento de acceso directo por URL a la incidencia de otro residente (HU-35 estricto)
    $resShowUnauthorized = $residenteBClient->get("/residente/incidencias/$incidenciaAId");
    verify($resShowUnauthorized->getStatusCode() === 403, 'Acceso prohibido HTTP 403 al intentar ver incidencia ajena por URL directa');

    // =========================================================================
    // SUITE 5: HU-36 & HU-37 - Gestión por Personal Autorizado (Admin y Conserje)
    // =========================================================================
    echo "\n--- SUITE 5: HU-36 & HU-37 - Personal Autorizado y Flujo de Estados ---\n";
    // 5.1 Conserje visualiza en bitácora
    $conserjeClient = sessionClient($baseUri);
    $loginCons = login($conserjeClient, $conserjeEmail);
    verify($loginCons->getStatusCode() === 302, 'Conserje inicia sesión');

    $resConsList = $conserjeClient->get('/conserje/incidencias');
    verify($resConsList->getStatusCode() === 200, 'Conserje accede a /conserje/incidencias (HTTP 200)');
    $htmlConsList = (string) $resConsList->getBody();
    verify(str_contains($htmlConsList, $tituloIncidencia), 'Conserje visualiza la incidencia vecinal reportada (HU-36)');
    verify(str_contains($htmlConsList, "401-$suffix"), 'Conserje visualiza el número de departamento asociado');

    // 5.2 Conserje cambia estado a 'En Proceso' (HU-37)
    $csrfTokenCons = tokenFrom($conserjeClient, "/conserje/incidencias/$incidenciaAId");
    $patchConsRes = $conserjeClient->patch("/conserje/incidencias/$incidenciaAId/estado", [
        'form_params' => [
            '_token' => $csrfTokenCons,
            'estado' => 'En Proceso',
            'respuesta_administracion' => 'Personal de mantenimiento fue notificado para inspección técnica.',
        ],
    ]);
    verify($patchConsRes->getStatusCode() === 302, 'Conserje actualiza estado a En Proceso con redirección 302');

    $stmtCheckInc = $db->query("SELECT estado, personal_resolucion_id, respuesta_administracion FROM incidencias WHERE id = $incidenciaAId")->fetch(PDO::FETCH_ASSOC);
    verify($stmtCheckInc['estado'] === 'En Proceso', 'Estado de la incidencia actualizado a En Proceso en BD');
    verify((int) $stmtCheckInc['personal_resolucion_id'] === $conserjeId, 'personal_resolucion_id registrado como el Conserje');

    // 5.3 Administrador visualiza y resuelve con respuesta oficial (HU-37)
    $resAdminShow = $adminClient->get("/admin/incidencias/$incidenciaAId");
    verify($resAdminShow->getStatusCode() === 200, 'Administrador accede al detalle de la incidencia (HTTP 200)');

    $respuestaFinal = "Se realizó recambio completo del balastro y tubo LED. Iluminación operativa al 100%.";
    $csrfTokenAdminInc = tokenFrom($adminClient, "/admin/incidencias/$incidenciaAId");
    $patchAdminRes = $adminClient->patch("/admin/incidencias/$incidenciaAId/estado", [
        'form_params' => [
            '_token' => $csrfTokenAdminInc,
            'estado' => 'Resuelta',
            'respuesta_administracion' => $respuestaFinal,
        ],
    ]);
    verify($patchAdminRes->getStatusCode() === 302, 'Administrador actualiza estado a Resuelta con redirección 302');

    $stmtResolved = $db->query("SELECT estado, personal_resolucion_id, respuesta_administracion, fecha_resolucion FROM incidencias WHERE id = $incidenciaAId")->fetch(PDO::FETCH_ASSOC);
    verify($stmtResolved['estado'] === 'Resuelta', 'Estado final actualizado a Resuelta en BD');
    verify(! empty($stmtResolved['fecha_resolucion']), 'fecha_resolucion registrada con timestamp válido');
    verify((int) $stmtResolved['personal_resolucion_id'] === $adminId, 'personal_resolucion_id actualizado al Administrador');

    // 5.4 Cambio visible inmediatamente para el Residente A
    $resShowUpdatedA = $residenteAClient->get("/residente/incidencias/$incidenciaAId");
    verify($resShowUpdatedA->getStatusCode() === 200, 'Residente A consulta incidencia resuelta');
    $htmlUpdatedA = (string) $resShowUpdatedA->getBody();
    verify(str_contains($htmlUpdatedA, 'Resuelta'), 'Residente A visualiza de inmediato el estado Resuelta');
    verify(str_contains($htmlUpdatedA, 'tubo LED'), 'Residente A visualiza la respuesta oficial de la administración');

    // 5.5 Control RBAC: Residente no puede manipular endpoint de cambio de estado
    $unauthPatch = $residenteAClient->patch("/admin/incidencias/$incidenciaAId/estado", [
        'form_params' => [
            '_token' => tokenFrom($residenteAClient, '/residente/incidencias'),
            'estado' => 'Rechazada',
        ],
    ]);
    verify($unauthPatch->getStatusCode() === 403, 'Residente bloqueado con HTTP 403 al intentar acceder a rutas administrativas');

    echo "\n=========================================================================\n";
    echo "  ¡TODAS LAS PRUEBAS DE SPRINT 4 (HU-31 A HU-37) PASARON EXITOSAMENTE!  \n";
    echo "=========================================================================\n";

} finally {
    // Limpieza de datos de prueba
    if (! empty($createdIncidenciaIds)) {
        $in = implode(',', array_fill(0, count($createdIncidenciaIds), '?'));
        $db->prepare("DELETE FROM incidencias WHERE id IN ($in)")->execute($createdIncidenciaIds);
    }
    if (! empty($createdAvisoIds)) {
        $in = implode(',', array_fill(0, count($createdAvisoIds), '?'));
        $db->prepare("DELETE FROM avisos WHERE id IN ($in)")->execute($createdAvisoIds);
    }
    if (! empty($createdDeptoIds)) {
        $in = implode(',', array_fill(0, count($createdDeptoIds), '?'));
        $db->prepare("DELETE FROM departamento_user WHERE departamento_id IN ($in)")->execute($createdDeptoIds);
        $db->prepare("DELETE FROM departamentos WHERE id IN ($in)")->execute($createdDeptoIds);
    }
    if (! empty($createdUserIds)) {
        $in = implode(',', array_fill(0, count($createdUserIds), '?'));
        $db->prepare("DELETE FROM users WHERE id IN ($in)")->execute($createdUserIds);
    }
}
