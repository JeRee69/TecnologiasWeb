<?php

// Integración HU-28: Control del tiempo de permanencia de encomiendas
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

function login(Client $client, string $email, string $password = 'PruebaHU28!123'): \Psr\Http\Message\ResponseInterface
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
    // 1. Crear Conserje
    $conserjeEmail = "conserje-hu28-$suffix@example.test";
    $insertUser = $db->prepare('INSERT INTO users (nombre, email, password, rol_id, estado, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
    $insertUser->execute(['Conserje HU28', $conserjeEmail, password_hash('PruebaHU28!123', PASSWORD_BCRYPT), $roles['Conserje'], 'activo']);
    $createdUserIds[] = $conserjeId = (int) $db->lastInsertId();

    // 2. Crear Departamentos
    $insertDepto = $db->prepare('INSERT INTO departamentos (numero, piso, estado, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())');
    $insertDepto->execute(["501-$suffix", 5, 'activo']);
    $createdDeptoIds[] = $deptoAId = (int) $db->lastInsertId();

    $insertDepto->execute(["502-$suffix", 5, 'activo']);
    $createdDeptoIds[] = $deptoBId = (int) $db->lastInsertId();

    // 3. Crear 3 encomiendas con distintas antigüedades:
    // - Encomienda Antigua (hace 6 días): para Depto A -> Permanencia prolongada
    // - Encomienda Media (hace 2 días): para Depto B
    // - Encomienda Reciente (hace 2 horas): para Depto A
    $fechaHace6Dias = date('Y-m-d H:i:s', strtotime('-6 days'));
    $fechaHace2Dias = date('Y-m-d H:i:s', strtotime('-2 days'));
    $fechaHace2Horas = date('Y-m-d H:i:s', strtotime('-2 hours'));

    $insertEnc = $db->prepare('INSERT INTO encomiendas (departamento_id, conserje_id, empresa_delivery, fecha_ingreso, estado, codigo_seguimiento, descripcion, created_at, updated_at) VALUES (?, ?, ?, ?, "Ingresado", ?, ?, NOW(), NOW())');

    $insertEnc->execute([$deptoAId, $conserjeId, 'Chilexpress', $fechaHace6Dias, 'ANTIGUO-06D', 'Caja olvidada 6 dias']);
    $createdEncomiendaIds[] = $encAntiguaId = (int) $db->lastInsertId();

    $insertEnc->execute([$deptoBId, $conserjeId, 'Starken', $fechaHace2Dias, 'MEDIO-02D', 'Paquete de 2 dias']);
    $createdEncomiendaIds[] = $encMediaId = (int) $db->lastInsertId();

    $insertEnc->execute([$deptoAId, $conserjeId, 'Mercado Libre', $fechaHace2Horas, 'RECIENTE-02H', 'Paquete de hace 2 horas']);
    $createdEncomiendaIds[] = $encRecienteId = (int) $db->lastInsertId();

    // 4. Conserje inicia sesión
    $conserjeClient = sessionClient();
    $loginRes = login($conserjeClient, $conserjeEmail);
    verify($loginRes->getStatusCode() === 302, 'Conserje inicia sesión');

    // 5. Conserje accede a la bitácora
    $vista = $conserjeClient->get('/conserje/encomiendas');
    verify($vista->getStatusCode() === 200, 'Conserje accede a /conserje/encomiendas (HTTP 200)');
    $html = (string) $vista->getBody();

    // 6. Criterio de Aceptación: El sistema calcula el tiempo transcurrido desde la recepción
    verify(str_contains($html, '6d') || str_contains($html, '6 d') || str_contains($html, '6 días'), 'Calcula permanencia de la encomienda de 6 días');
    verify(str_contains($html, '2d') || str_contains($html, '2 d') || str_contains($html, '2 días'), 'Calcula permanencia de la encomienda de 2 días');
    verify(str_contains($html, '2 h') || str_contains($html, '2h') || str_contains($html, '2 hora'), 'Calcula permanencia de la encomienda de 2 horas');

    // 7. Criterio de Aceptación: Identificar aquellas con mayor tiempo de permanencia
    // La encomienda de 6 días debe tener el distintivo de permanencia prolongada
    verify(str_contains($html, 'Prolongado'), 'Insignia de permanencia prolongada visible para encomiendas de más de 3 días');
    verify(str_contains($html, "Depto. 501-$suffix"), 'Departamento de mayor tiempo en custodia identificado');

    // 8. Ordenamiento por mayor tiempo de permanencia
    $vistaOrdenada = $conserjeClient->get('/conserje/encomiendas?orden=mayor_permanencia');
    verify($vistaOrdenada->getStatusCode() === 200, 'Acceso a bitácora ordenada por mayor permanencia');
    $htmlOrdenado = (string) $vistaOrdenada->getBody();

    // Comprobar que en el HTML la encomienda antigua aparece antes que la reciente
    $posAntigua = strpos($htmlOrdenado, 'ANTIGUO-06D');
    $posMedia = strpos($htmlOrdenado, 'MEDIO-02D');
    $posReciente = strpos($htmlOrdenado, 'RECIENTE-02H');

    verify($posAntigua !== false && $posMedia !== false && $posReciente !== false, 'Todas las encomiendas presentes en vista ordenada');
    verify($posAntigua < $posMedia && $posMedia < $posReciente, 'Las encomiendas con mayor permanencia se ordenan en primer lugar');

    // 9. Filtrado por paquetes con permanencia prolongada (> 3 días)
    $vistaProlongadas = $conserjeClient->get('/conserje/encomiendas?filtro=prolongadas');
    verify($vistaProlongadas->getStatusCode() === 200, 'Acceso a filtro de encomiendas prolongadas');
    $htmlProlongadas = (string) $vistaProlongadas->getBody();

    verify(str_contains($htmlProlongadas, 'ANTIGUO-06D'), 'Encomienda prolongada (> 3 días) visible en filtro');
    verify(! str_contains($htmlProlongadas, 'MEDIO-02D'), 'Encomienda de 2 días excluida de filtro de prolongadas');
    verify(! str_contains($htmlProlongadas, 'RECIENTE-02H'), 'Encomienda de 2 horas excluida de filtro de prolongadas');

    echo "HU-28: criterios de aceptación verificados exitosamente.\n";

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
