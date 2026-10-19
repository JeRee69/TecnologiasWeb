<?php

// Integración HU-03: Creación de usuarios y asignación de roles
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

function login(Client $client, string $email, string $password = 'PruebaHU03!123'): \Psr\Http\Message\ResponseInterface
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
$createdIds = [];

try {
    // Crear cuenta de administrador para la prueba
    $adminEmail = "admin-hu03-$suffix@example.test";
    $insertAdmin = $db->prepare('INSERT INTO users (nombre, email, password, rol_id, estado, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
    $insertAdmin->execute(['Admin HU03', $adminEmail, password_hash('PruebaHU03!123', PASSWORD_BCRYPT), $roles['Administrador'], 'activo']);
    $createdIds[] = (int) $db->lastInsertId();

    // Crear cuenta de residente para probar restricción de rol
    $residenteEmail = "residente-hu03-$suffix@example.test";
    $insertRes = $db->prepare('INSERT INTO users (nombre, email, password, rol_id, estado, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
    $insertRes->execute(['Residente HU03', $residenteEmail, password_hash('PruebaHU03!123', PASSWORD_BCRYPT), $roles['Residente'], 'activo']);
    $createdIds[] = (int) $db->lastInsertId();

    // 1. Administrador inicia sesión
    $adminClient = sessionClient();
    $loginRes = login($adminClient, $adminEmail);
    verify($loginRes->getStatusCode() === 302, 'Administrador inicia sesión');

    // 2. Administrador accede a la vista de usuarios y observa el formulario
    $vistaUsuarios = $adminClient->get('/admin/usuarios');
    verify($vistaUsuarios->getStatusCode() === 200, 'Administrador visualiza vista de usuarios');
    $htmlVista = (string) $vistaUsuarios->getBody();
    verify(str_contains($htmlVista, 'Registrar usuario'), 'Formulario de registro visible en la vista');
    verify(str_contains($htmlVista, 'name="email"'), 'Campo de correo electrónico presente en formulario');
    verify(str_contains($htmlVista, 'name="rol_id"'), 'Campo de selección de rol presente en formulario');

    // 3. Crear usuario con datos obligatorios y asignación de rol válido (Conserje con clave autogenerada)
    $nuevoEmail = "nuevo-conserje-$suffix@example.test";
    $csrf = tokenFrom($adminClient, '/admin/usuarios');
    $postCrear = $adminClient->post('/admin/usuarios', [
        'form_params' => [
            '_token' => $csrf,
            'nombre' => 'Nuevo Conserje Test',
            'email' => $nuevoEmail,
            'rol_id' => $roles['Conserje'],
            'rut' => '19.876.543-2',
            'telefono' => '+56911223344',
            'password' => '', // Vía autogeneración
        ],
    ]);
    verify($postCrear->getStatusCode() === 302, 'Creación de usuario responde redirección tras guardar');

    // Comprobar inserción en BD
    $stmt = $db->prepare('SELECT id, nombre, email, password, rol_id, estado FROM users WHERE email = ?');
    $stmt->execute([$nuevoEmail]);
    $userBD = $stmt->fetch(PDO::FETCH_ASSOC);
    verify($userBD !== false, 'Usuario insertado correctamente en base de datos');
    $createdIds[] = (int) $userBD['id'];
    verify((int) $userBD['rol_id'] === (int) $roles['Conserje'], 'Rol Conserje asignado correctamente');
    verify($userBD['estado'] === 'activo', 'Usuario creado en estado activo');
    verify(!empty($userBD['password']), 'Credencial (hash de contraseña) generada en base de datos');

    // Comprobar que en la vista redirigida se muestran las credenciales generadas
    $vistaPostRedirect = $adminClient->get('/admin/usuarios');
    $htmlRedirect = (string) $vistaPostRedirect->getBody();
    verify(str_contains($htmlRedirect, 'Credenciales generadas para la nueva cuenta'), 'Mensaje de credenciales generadas mostrado al administrador');
    verify(str_contains($htmlRedirect, $nuevoEmail), 'Correo mostrado en el resumen de credenciales');

    // 4. El sistema no permite registrar correos duplicados
    $csrfDuplicado = tokenFrom($adminClient, '/admin/usuarios');
    $postDuplicado = $adminClient->post('/admin/usuarios', [
        'form_params' => [
            '_token' => $csrfDuplicado,
            'nombre' => 'Otro Conserje',
            'email' => $nuevoEmail, // Correo ya existente
            'rol_id' => $roles['Conserje'],
        ],
    ]);
    verify($postDuplicado->getStatusCode() === 302, 'Intento de correo duplicado es redirigido con error');
    $vistaError = $adminClient->get('/admin/usuarios');
    verify(str_contains((string) $vistaError->getBody(), 'El correo electrónico ya se encuentra registrado'), 'Error de correo duplicado mostrado');

    // Verificar que no se creó un registro duplicado
    $countStmt = $db->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
    $countStmt->execute([$nuevoEmail]);
    verify((int) $countStmt->fetchColumn() === 1, 'No existe duplicidad en base de datos');

    // 5. Validación de datos obligatorios faltantes
    $csrfInvalido = tokenFrom($adminClient, '/admin/usuarios');
    $postInvalido = $adminClient->post('/admin/usuarios', [
        'form_params' => [
            '_token' => $csrfInvalido,
            'nombre' => '', // Falta nombre
            'email' => '',  // Falta email
            'rol_id' => '', // Falta rol
        ],
    ]);
    verify($postInvalido->getStatusCode() === 302, 'Petición con campos vacíos rechazada');

    // 6. Validación de rol inválido
    $csrfRolInvalido = tokenFrom($adminClient, '/admin/usuarios');
    $postRolInvalido = $adminClient->post('/admin/usuarios', [
        'form_params' => [
            '_token' => $csrfRolInvalido,
            'nombre' => 'Usuario Rol Invalido',
            'email' => "rol-invalido-$suffix@example.test",
            'rol_id' => 999999, // Rol inexistente
        ],
    ]);
    verify($postRolInvalido->getStatusCode() === 302, 'Rol inexistente rechazado por validación');

    // 7. Registro con contraseña manual provista
    $residenteManualEmail = "residente-manual-$suffix@example.test";
    $csrfManual = tokenFrom($adminClient, '/admin/usuarios');
    $adminClient->post('/admin/usuarios', [
        'form_params' => [
            '_token' => $csrfManual,
            'nombre' => 'Residente Clave Manual',
            'email' => $residenteManualEmail,
            'rol_id' => $roles['Residente'],
            'password' => 'MiClaveSegura2026!',
        ],
    ]);
    $stmtManual = $db->prepare('SELECT id, password FROM users WHERE email = ?');
    $stmtManual->execute([$residenteManualEmail]);
    $userManual = $stmtManual->fetch(PDO::FETCH_ASSOC);
    verify($userManual !== false, 'Usuario con contraseña manual registrado correctamente');
    $createdIds[] = (int) $userManual['id'];

    // Iniciar sesión con la cuenta creada manualmente
    $residenteClient = sessionClient();
    $loginResidente = login($residenteClient, $residenteManualEmail, 'MiClaveSegura2026!');
    verify($loginResidente->getStatusCode() === 302, 'Nuevo usuario puede iniciar sesión con sus credenciales asignadas');
    $redirLocation = $loginResidente->getHeaderLine('Location');
    verify(str_contains($redirLocation, '/residente/dashboard'), 'Nuevo residente es redirigido a su dashboard correspondiente');

    // 8. Control de acceso RBAC: Residente no puede acceder a POST /admin/usuarios
    $csrfResidente = tokenFrom($residenteClient, '/residente/dashboard');
    $postDesdeResidente = $residenteClient->post('/admin/usuarios', [
        'form_params' => [
            '_token' => $csrfResidente,
            'nombre' => 'Hack Attempt',
            'email' => "hack-$suffix@example.test",
            'rol_id' => $roles['Administrador'],
        ],
    ]);
    verify($postDesdeResidente->getStatusCode() === 403, 'Residente no autorizado para crear usuarios (HTTP 403)');

    // 9. Invitado no autenticado no puede acceder
    $guestClient = sessionClient();
    $postGuest = $guestClient->post('/admin/usuarios', [
        'form_params' => [
            '_token' => 'token-invalido',
            'nombre' => 'Guest Attempt',
            'email' => "guest-$suffix@example.test",
            'rol_id' => $roles['Administrador'],
        ],
    ]);
    verify($postGuest->getStatusCode() === 302 || $postGuest->getStatusCode() === 419, 'Petición sin autenticación es rechazada');

    echo "HU-03: criterios de aceptación verificados exitosamente.\n";

} finally {
    if (!empty($createdIds)) {
        $placeholders = implode(',', array_fill(0, count($createdIds), '?'));
        $db->prepare("DELETE FROM users WHERE id IN ($placeholders)")->execute($createdIds);
    }
}
