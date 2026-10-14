<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $error) {
    fwrite(STDERR, $error->getMessage()."\n");
    exit(1);
});

use App\Models\EspacioComun;
use App\Models\User;
use App\Models\Rol;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

function configCheck(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
    echo "PASS: $message\n";
}

function configBrowser(): Client
{
    return new Client(['base_uri' => 'http://web', 'cookies' => new CookieJar,
        'allow_redirects' => false, 'http_errors' => false, 'timeout' => 15]);
}

function configToken(Client $client, string $path): string
{
    $response = $client->get($path);
    if ($response->getStatusCode() !== 200 || ! preg_match('/name="_token" value="([^"]+)"/', (string) $response->getBody(), $matches)) {
        throw new RuntimeException("No se pudo abrir $path");
    }
    return $matches[1];
}

function configLogin(string $email): Client
{
    $client = configBrowser();
    $response = $client->post('/login', ['form_params' => ['_token' => configToken($client, '/login'),
        'identifier' => $email, 'password' => 'PruebaHU20!123']]);
    configCheck($response->getStatusCode() === 302 && ! str_ends_with($response->getHeaderLine('Location'), '/login'), 'Cuenta temporal autenticada');
    return $client;
}

$users = [];
$spaces = [];
$suffix = bin2hex(random_bytes(6));
try {
    foreach (Rol::all() as $rol) {
        $users[$rol->nombre] = User::create(['nombre' => "HU20 {$rol->nombre}",
            'email' => "hu20-{$rol->id}-$suffix@example.test", 'password' => 'PruebaHU20!123',
            'rol_id' => $rol->id, 'estado' => 'activo']);
    }
    foreach (['activo', 'inactivo'] as $state) {
        $spaces[$state] = EspacioComun::create(['nombre' => "HU20 $suffix $state", 'descripcion' => 'Espacio temporal de prueba', 'estado' => $state]);
    }
    $space = $spaces['activo'];
    $other = $spaces['inactivo'];
    $otherBefore = $other->refresh()->getRawOriginal();
    $path = "/admin/espacios-comunes/$space->id/configuracion";
    $admin = configLogin($users['Administrador']->email);
    $token = configToken($admin, $path);
    configCheck(! $space->tieneConfiguracion(), 'Espacio nuevo comienza con configuración pendiente');
    $valid = ['aforo' => '40', 'hora_apertura' => '09:00', 'hora_cierre' => '22:30',
        'costo' => '0', 'reglas' => "Dejar limpio.\n<script>alert(1)</script>"];
    $send = fn (array $data) => $admin->patch($path, ['headers' => ['Accept' => 'application/json'],
        'form_params' => array_merge(['_token' => $token], $data)]);
    $response = $send(array_merge($valid, ['id' => $other->id, 'nombre' => 'Alterado', 'estado' => 'inactivo']));
    configCheck($response->getStatusCode() === 302 && str_ends_with($response->getHeaderLine('Location'), $path), 'Administrador guarda configuración del espacio seleccionado');
    $space->refresh();
    configCheck($space->tieneConfiguracion() && $space->aforo === 40 && $space->costo === '0' &&
        $space->hora_apertura === '09:00:00' && $space->hora_cierre === '22:30:00' && $space->reglas === $valid['reglas'], 'Aforo, horario, costo gratuito y reglas persisten');
    configCheck($space->nombre === "HU20 $suffix activo" && $space->estado === 'activo' &&
        $otherBefore === $other->refresh()->getRawOriginal(), 'La configuración no modifica otros campos ni otros espacios');
    $fresh = configLogin($users['Administrador']->email);
    $html = (string) $fresh->get($path)->getBody();
    configCheck(str_contains($html, 'value="40"') && str_contains($html, 'value="0"') && str_contains($html, 'value="09:00"') &&
        str_contains($html, 'value="22:30"') && str_contains($html, '&lt;script&gt;alert(1)&lt;/script&gt;'), 'Formulario recupera la configuración y escapa las reglas desde una sesión nueva');
    $html = (string) $fresh->get('/admin/espacios-comunes')->getBody();
    configCheck(str_contains($html, '40 personas') && str_contains($html, 'Gratuito') && str_contains($html, $path), 'Listado muestra un resumen y acceso a editar');
    $before = $space->getRawOriginal();
    foreach ([[], ['aforo' => 0], ['aforo' => -1], ['aforo' => 1.5], ['aforo' => 100001], ['aforo' => ['40']],
        ['hora_apertura' => '25:00'], ['hora_cierre' => '09:00'], ['hora_cierre' => '08:00'], ['hora_apertura' => ['09:00']],
        ['costo' => -1], ['costo' => '12.5'], ['costo' => '1000000000000'], ['costo' => ['10']],
        ['reglas' => ''], ['reglas' => ['texto']], ['reglas' => str_repeat('a', 5001)],
    ] as $index => $invalid) {
        $response = $send($index === 0 ? [] : array_merge($valid, $invalid));
        configCheck($response->getStatusCode() === 422 && $before === $space->refresh()->getRawOriginal(), 'Configuración inválida rechazada sin guardar cambios parciales');
    }
    $admin->patch($path, ['headers' => ['Referer' => 'http://web'.$path],
        'form_params' => array_merge(['_token' => $token], $valid, ['aforo' => 0, 'reglas' => 'Mantener limpio'])]);
    $html = (string) $admin->get($path)->getBody();
    configCheck(str_contains($html, 'al menos una persona') && str_contains($html, 'Mantener limpio'), 'Errores se muestran conservando lo escrito');
    configCheck($send(array_merge($valid, ['aforo' => 25, 'costo' => '15000']))->getStatusCode() === 302 &&
        $space->refresh()->aforo === 25 && $space->costo === '15000', 'Administrador puede modificar una configuración existente');
    configCheck($admin->patch($path, ['form_params' => $valid])->getStatusCode() === 419, 'Configuración requiere CSRF');
    $otherPath = "/admin/espacios-comunes/$other->id/configuracion";
    configCheck($admin->patch($otherPath, ['form_params' => array_merge(['_token' => $token], $valid)])->getStatusCode() === 302 &&
        $other->refresh()->tieneConfiguracion(), 'Se puede preparar la configuración de un espacio inactivo');
    configCheck($admin->get('/admin/espacios-comunes/2147483647/configuracion')->getStatusCode() === 404 &&
        $admin->patch('/admin/espacios-comunes/2147483647/configuracion', ['form_params' => array_merge(['_token' => $token], $valid)])->getStatusCode() === 404, 'Espacio inexistente devuelve 404');
    $statePath = "/admin/espacios-comunes/$space->id/estado";
    $withoutState = fn (EspacioComun $item) => array_diff_key($item->refresh()->getRawOriginal(), array_flip(['estado', 'updated_at']));
    $settingsBefore = $withoutState($space);
    $otherBefore = $other->refresh()->getRawOriginal();
    $stateRequest = fn (array $data) => $admin->patch($statePath, ['headers' => ['Accept' => 'application/json'],
        'form_params' => array_merge(['_token' => $token], $data)]);
    foreach (['inactivo', 'inactivo', 'activo'] as $state) {
        configCheck($stateRequest(['estado' => $state, 'aforo' => 999, 'nombre' => 'Alterado', 'id' => $other->id])->getStatusCode() === 302 &&
            $space->refresh()->estado === $state, "Administrador establece estado $state sin invertir solicitudes repetidas");
        configCheck($settingsBefore === $withoutState($space) && $otherBefore === $other->refresh()->getRawOriginal(), 'Cambio de estado conserva datos y configuración, sin afectar otros espacios');
        configCheck(EspacioComun::activos()->whereKey($space->id)->exists() === ($state === 'activo'), 'Consulta de activos respeta el nuevo estado');
        $listing = (string) $admin->get('/admin/espacios-comunes')->getBody();
        configCheck(str_contains($listing, ($state === 'activo' ? 'Desactivar espacio ' : 'Activar espacio ').$space->nombre), 'Listado ofrece la acción correspondiente al nuevo estado');
    }
    foreach ([[], ['estado' => 'reservado'], ['estado' => ['activo']]] as $invalid) {
        configCheck($stateRequest($invalid)->getStatusCode() === 422 && $space->refresh()->estado === 'activo', 'Estado ausente o inválido rechazado');
    }
    configCheck($admin->patch($statePath, ['form_params' => ['estado' => 'inactivo']])->getStatusCode() === 419, 'Cambio de estado requiere CSRF');
    configCheck($admin->patch('/admin/espacios-comunes/2147483647/estado', ['form_params' => ['_token' => $token, 'estado' => 'activo']])->getStatusCode() === 404, 'No se puede cambiar el estado de un espacio inexistente');
    $before = $space->getRawOriginal();
    foreach (['Conserje', 'Residente'] as $role) {
        $client = configLogin($users[$role]->email);
        $csrf = configToken($client, '/perfil');
        configCheck($client->get($path)->getStatusCode() === 403 && $client->patch($path, ['form_params' => array_merge(['_token' => $csrf], $valid)])->getStatusCode() === 403, "$role no puede consultar ni modificar configuración");
        configCheck($client->patch($statePath, ['form_params' => ['_token' => $csrf, 'estado' => 'inactivo']])->getStatusCode() === 403, "$role no puede cambiar el estado del espacio");
    }
    $stateAdmin = configLogin($users['Administrador']->email);
    $stateAdminToken = configToken($stateAdmin, '/perfil');
    $users['Administrador']->update(['estado' => 'inactivo']);
    configCheck($stateAdmin->patch($statePath, ['form_params' => ['_token' => $stateAdminToken, 'estado' => 'inactivo']])->getStatusCode() === 302, 'Administrador inactivo no puede cambiar el estado del espacio');
    configCheck($admin->patch($path, ['form_params' => array_merge(['_token' => $token], $valid)])->getStatusCode() === 302 &&
        $fresh->get($path)->getStatusCode() === 302, 'Administrador inactivo pierde acceso a la configuración');
    $guest = configBrowser();
    configCheck($guest->patch($statePath, ['form_params' => ['_token' => configToken($guest, '/login'), 'estado' => 'inactivo']])->getStatusCode() === 302, 'Invitado no puede cambiar el estado del espacio');
    configCheck($guest->get($path)->getStatusCode() === 302 && $guest->patch($path, ['form_params' => array_merge(['_token' => configToken($guest, '/login')], $valid)])->getStatusCode() === 302, 'Invitado no puede consultar ni modificar configuración');
    configCheck($before === $space->refresh()->getRawOriginal(), 'Peticiones no autorizadas no cambian la configuración');
    echo "HU-20: configuración de espacios comunes verificada.\n";
} finally {
    foreach ($spaces as $space) { $space->delete(); }
    foreach ($users as $user) { $user->delete(); }
}
