<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $error) {
    fwrite(STDERR, $error->getMessage()."\n");
    exit(1);
});

use App\Models\EspacioComun;
use App\Models\Reserva;
use App\Models\Rol;
use App\Models\User;
use App\Services\DisponibilidadEspacio;
use Carbon\CarbonImmutable;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

function availabilityCheck(bool $condition, string $message): void
{
    if (! $condition) { throw new RuntimeException($message); }
    echo "PASS: $message\n";
}

function availabilityBrowser(): Client
{
    return new Client(['base_uri' => 'http://web', 'cookies' => new CookieJar,
        'allow_redirects' => false, 'http_errors' => false, 'timeout' => 15]);
}

$users = $spaces = [];
$suffix = bin2hex(random_bytes(6));
$path = '/residente/espacios-comunes/disponibilidad';
$date = now()->addDays(2)->toDateString();
try {
    foreach (Rol::all() as $role) {
        $users[$role->nombre] = User::create(['nombre' => "HU21 $role->nombre", 'email' => "hu21-$role->id-$suffix@example.test",
            'password' => 'PruebaHU21!123', 'rol_id' => $role->id, 'estado' => 'activo']);
    }
    foreach (['principal', 'otro', 'inactivo', 'pendiente'] as $type) {
        $spaces[$type] = EspacioComun::create(['nombre' => "HU21 $suffix $type", 'descripcion' => '<script>privado</script>',
            'estado' => $type === 'inactivo' ? 'inactivo' : 'activo'] + ($type === 'pendiente' ? [] : [
                'aforo' => 15, 'hora_apertura' => '09:15', 'hora_cierre' => '13:45', 'costo' => 0, 'reglas' => 'Mantener limpio.']));
    }
    $space = $spaces['principal'];
    $reserve = fn ($id, $day, $start, $end, $state) => Reserva::create(['espacio_comun_id' => $id,
        'user_id' => $users['Residente']->id, 'fecha' => $day, 'hora_inicio' => $start, 'hora_fin' => $end, 'estado' => $state]);
    $reserve($space->id, $date, '10:00', '10:30', 'confirmada');
    $reserve($space->id, $date, '11:15', '12:15', 'pendiente');
    $reserve($space->id, $date, '12:15', '13:45', 'cancelada');
    $reserve($space->id, now()->addDays(3)->toDateString(), '12:15', '13:45', 'confirmada');
    $reserve($spaces['otro']->id, $date, '12:15', '13:45', 'confirmada');
    $service = app(DisponibilidadEspacio::class);
    $blocks = $service->bloques($space, $date);
    availabilityCheck(array_column($blocks, 'estado') === ['ocupado', 'ocupado', 'ocupado', 'disponible', 'disponible'],
        'Solapamientos parciales y pendientes ocupan bloques; cancelaciones, otras fechas y espacios no interfieren');
    availabilityCheck($blocks[4]['inicio'] === '13:15' && $blocks[4]['fin'] === '13:45', 'Último bloque respeta el cierre con minutos');
    availabilityCheck($service->bloques($spaces['inactivo'], $date) === [] && $service->bloques($spaces['pendiente'], $date) === [],
        'Espacios inactivos o sin configuración no generan disponibilidad');
    CarbonImmutable::setTestNow(CarbonImmutable::parse($date.' 12:30', config('app.timezone')));
    availabilityCheck(array_column($service->bloques($space, $date), 'estado') === ['ocupado', 'ocupado', 'ocupado', 'pasado', 'disponible'],
        'Bloques ya iniciados se deshabilitan y los ocupados conservan su estado');
    CarbonImmutable::setTestNow();
    availabilityCheck(availabilityBrowser()->get($path)->getStatusCode() === 302, 'Invitado debe iniciar sesión');
    foreach ($users as $role => $user) {
        $client = availabilityBrowser();
        preg_match('/name="_token" value="([^"]+)"/', (string) $client->get('/login')->getBody(), $token);
        $login = $client->post('/login', ['form_params' => ['_token' => $token[1], 'identifier' => $user->email, 'password' => 'PruebaHU21!123']]);
        availabilityCheck($login->getStatusCode() === 302, "Login temporal $role");
        if ($role !== 'Residente') {
            availabilityCheck($client->get($path)->getStatusCode() === 403, "$role no accede al portal de residentes");
            continue;
        }
        availabilityCheck(str_contains((string) $client->get('/residente/dashboard')->getBody(), $path), 'Portal enlaza a disponibilidad');
        $query = ['espacio_id' => $space->id, 'fecha' => $date];
        $response = $client->get($path, ['query' => $query]);
        $html = (string) $response->getBody();
        availabilityCheck($response->getStatusCode() === 200 && str_contains($response->getHeaderLine('Cache-Control'), 'no-store'), 'Consulta autenticada responde sin caché');
        preg_match_all('/<input type="radio"[^>]+>/', $html, $inputs);
        availabilityCheck(count($inputs[0]) === 5 && count(array_filter($inputs[0], fn ($input) => str_contains($input, 'disabled'))) === 3,
            'HTML deshabilita los tres bloques ocupados y permite los dos libres');
        availabilityCheck(str_contains($html, '&lt;script&gt;privado&lt;/script&gt;') && ! str_contains($html, $user->email)
            && ! str_contains($html, $spaces['inactivo']->nombre), 'Escapa texto y no expone residentes ni espacios inactivos');
        availabilityCheck(str_contains($html, 'value="'.$date.'"') && preg_match('/value="'.$space->id.'"\s+selected/', $html), 'Conserva espacio y fecha');
        foreach ([['fecha' => '2026-02-30'], ['fecha' => ['2026-10-20']], ['espacio_id' => ['1']], ['espacio_id' => 'abc']] as $invalid) {
            availabilityCheck($client->get($path, ['query' => array_merge($query, $invalid)])->getStatusCode() === 422, 'Filtro malformado se rechaza sin error de servidor');
        }
        foreach ([$spaces['inactivo']->id, 2147483647] as $id) {
            availabilityCheck($client->get($path, ['query' => ['espacio_id' => $id, 'fecha' => $date]])->getStatusCode() === 404, 'Espacio inactivo o inexistente no es consultable');
        }
        $pending = (string) $client->get($path, ['query' => ['espacio_id' => $spaces['pendiente']->id, 'fecha' => $date]])->getBody();
        availabilityCheck(str_contains($pending, 'configuración pendiente') && ! str_contains($pending, 'type="radio"'), 'Configuración pendiente no ofrece bloques');
        $past = (string) $client->get($path, ['query' => array_merge($query, ['fecha' => now()->subDay()->toDateString()])])->getBody();
        availabilityCheck(substr_count($past, ' disabled') === 5 && str_contains($past, 'No hay bloques disponibles'), 'Fecha pasada no ofrece horarios seleccionables');
        // Los cambios persistidos se reflejan al consultar nuevamente.
        $reserve($space->id, $date, '12:15', '13:45', 'confirmada');
        $full = (string) $client->get($path, ['query' => $query])->getBody();
        availabilityCheck(substr_count($full, ' disabled') === 5 && str_contains($full, 'No hay bloques disponibles'), 'Nueva reserva actualiza disponibilidad y día completo');
        $user->update(['estado' => 'inactivo']);
        availabilityCheck($client->get($path)->getStatusCode() === 302, 'Residente desactivado pierde acceso');
    }
    echo "HU-21: disponibilidad de espacios verificada.\n";
} finally {
    CarbonImmutable::setTestNow();
    Reserva::whereIn('espacio_comun_id', array_map(fn ($space) => $space->id, $spaces))->delete();
    foreach ($spaces as $space) { $space->delete(); }
    foreach ($users as $user) { $user->delete(); }
}
