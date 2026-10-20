<?php

// Verifica el criterio de solapamiento a través del calendario y la confirmación HTTP.
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
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

function overlapCheck(bool $condition, string $message): void
{
    if (! $condition) { throw new RuntimeException($message); }
    echo "PASS: $message\n";
}

$users = $spaces = [];
$suffix = bin2hex(random_bytes(6));
$path = '/residente/reservas';
$calendar = '/residente/espacios-comunes/disponibilidad';
$date = now()->addDays(4)->toDateString();
$message = 'El horario ya no está disponible: coincide total o parcialmente con una reserva existente.';
try {
    foreach (['solicitante', 'titular'] as $type) {
        $users[$type] = User::create(['nombre' => "HU23 $type", 'email' => "hu23-$type-$suffix@example.test",
            'password' => 'PruebaHU23!123', 'rol_id' => Rol::where('nombre', 'Residente')->firstOrFail()->id, 'estado' => 'activo']);
    }
    foreach (['principal', 'otro'] as $type) {
        $spaces[$type] = EspacioComun::create(['nombre' => "HU23 $suffix $type", 'descripcion' => 'Prueba de conflictos',
            'estado' => 'activo', 'aforo' => 20, 'hora_apertura' => '09:00', 'hora_cierre' => '14:00', 'costo' => 0, 'reglas' => 'Respetar horario.']);
    }
    $client = new Client(['base_uri' => 'http://web', 'cookies' => new CookieJar,
        'allow_redirects' => false, 'http_errors' => false, 'timeout' => 20]);
    preg_match('/name="_token" value="([^"]+)"/', (string) $client->get('/login')->getBody(), $token);
    $login = $client->post('/login', ['form_params' => ['_token' => $token[1], 'identifier' => $users['solicitante']->email, 'password' => 'PruebaHU23!123']]);
    overlapCheck($login->getStatusCode() === 302 && !str_ends_with($login->getHeaderLine('Location'), '/login'), 'Residente de prueba autenticado');
    preg_match('/name="_token" value="([^"]+)"/', (string) $client->get('/perfil')->getBody(), $token);
    $query = ['espacio_id' => $spaces['principal']->id, 'fecha' => $date];
    $data = ['_token' => $token[1], 'bloque' => '10:00–11:00'] + $query;
    $cases = [
        ['Coincidencia exacta', '10:00', '11:00', true],
        ['Solapamiento por el inicio', '09:30', '10:30', true],
        ['Solapamiento por el final', '10:30', '11:30', true],
        ['Reserva contenida en el bloque', '10:15', '10:45', true],
        ['Reserva que contiene el bloque', '09:00', '12:00', true],
        ['Un segundo ocupado al inicio', '09:30:00', '10:00:01', true],
        ['Un segundo ocupado al final', '10:59:59', '11:30:00', true],
        ['Reserva contigua anterior', '09:00', '10:00', false],
        ['Reserva contigua posterior', '11:00', '12:00', false],
        ['Reserva anterior sin contacto', '09:00', '09:30', false],
        ['Reserva posterior sin contacto', '12:00', '13:00', false],
        ['Reserva pendiente', '10:00', '11:00', true, 'pendiente'],
        ['Reserva cancelada', '10:00', '11:00', false, 'cancelada'],
        ['Mismo horario en otra fecha', '10:00', '11:00', false, 'confirmada', 'otra_fecha'],
        ['Mismo horario en otro espacio', '10:00', '11:00', false, 'confirmada', 'otro_espacio'],
    ];
    foreach ($cases as $case) {
        [$label, $start, $end, $conflict] = $case;
        // Primero se consulta libre; la reserva de otro residente aparece después.
        $before = (string) $client->get($calendar, ['query' => $query])->getBody();
        preg_match('/<input type="radio"[^>]*value="10:00–11:00"[^>]*>/', $before, $input);
        overlapCheck(isset($input[0]) && !str_contains($input[0], 'disabled'), "$label: calendario inicial libre");
        $existing = Reserva::create(['espacio_comun_id' => $spaces[($case[5] ?? '') === 'otro_espacio' ? 'otro' : 'principal']->id,
            'user_id' => $users['titular']->id, 'fecha' => ($case[5] ?? '') === 'otra_fecha' ? now()->addDays(5)->toDateString() : $date,
            'hora_inicio' => $start, 'hora_fin' => $end, 'estado' => $case[4] ?? 'confirmada']);
        $snapshot = $existing->refresh()->getRawOriginal();
        $response = $client->post($path, ['headers' => ['Accept' => 'application/json'], 'form_params' => $data]);
        $own = Reserva::where('user_id', $users['solicitante']->id);
        overlapCheck($response->getStatusCode() === ($conflict ? 422 : 302) && $own->count() === ($conflict ? 0 : 1),
            "$label: ".($conflict ? 'rechaza confirmación sin guardar' : 'permite confirmar'));
        overlapCheck($existing->refresh()->getRawOriginal() === $snapshot, "$label: conserva la reserva existente");
        // Retirar solo la confirmación aceptada para inspeccionar el calendario con el registro original.
        $own->delete();
        if ($conflict) {
            $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            overlapCheck(str_contains($body['errors']['bloque'][0] ?? '', $message), "$label: informa horario no disponible");
        }
        $html = (string) $client->get($calendar, ['query' => $query])->getBody();
        preg_match('/<input type="radio"[^>]*value="10:00–11:00"[^>]*>/', $html, $input);
        overlapCheck(isset($input[0]) && str_contains($input[0], 'disabled') === $conflict, "$label: calendario refleja disponibilidad");
        if ($conflict) {
            $client->post($path, ['headers' => ['Referer' => 'http://web'.$calendar.'?'.http_build_query($query)], 'form_params' => $data]);
            $html = (string) $client->get($calendar, ['query' => $query])->getBody();
            overlapCheck(str_contains($html, $message) && str_contains($html, 'role="alert"')
                && !str_contains($html, $users['titular']->email), "$label: muestra error visible sin datos de otro residente");
        }
        $existing->delete();
    }
    echo "HU-23: prevención de reservas solapadas verificada.\n";
} finally {
    Reserva::whereIn('espacio_comun_id', array_map(fn ($space) => $space->id, $spaces))->delete();
    foreach ($spaces as $space) { $space->delete(); }
    foreach ($users as $user) { $user->delete(); }
}
