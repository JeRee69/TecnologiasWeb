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
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Promise\Utils;

function cancelCheck(bool $condition, string $message): void
{
    if (! $condition) { throw new RuntimeException($message); }
    echo "PASS: $message\n";
}

function cancelBrowser(): Client
{
    return new Client(['base_uri' => 'http://web', 'cookies' => new CookieJar,
        'allow_redirects' => false, 'http_errors' => false, 'timeout' => 20]);
}

function cancelToken(Client $client, string $path): string
{
    $response = $client->get($path);
    if ($response->getStatusCode() !== 200 || !preg_match('/name="_token" value="([^"]+)"/', (string) $response->getBody(), $token)) {
        throw new RuntimeException('No se pudo obtener CSRF');
    }
    return $token[1];
}

function cancelLogin(User $user): Client
{
    $client = cancelBrowser();
    $response = $client->post('/login', ['form_params' => ['_token' => cancelToken($client, '/login'),
        'identifier' => $user->email, 'password' => 'PruebaCancel!123']]);
    cancelCheck($response->getStatusCode() === 302 && !str_ends_with($response->getHeaderLine('Location'), '/login'), 'Cuenta temporal autenticada');
    return $client;
}

$users = [];
$space = null;
$suffix = bin2hex(random_bytes(6));
$date = now()->addDays(3)->toDateString();
try {
    foreach (['propio', 'otro', 'Administrador', 'Conserje'] as $type) {
        $users[$type] = User::create(['nombre' => "Cancelación $type", 'email' => "cancel-$type-$suffix@example.test", 'password' => 'PruebaCancel!123',
            'rol_id' => Rol::where('nombre', in_array($type, ['Administrador', 'Conserje']) ? $type : 'Residente')->firstOrFail()->id, 'estado' => 'activo']);
    }
    $space = EspacioComun::create(['nombre' => "Cancelación $suffix", 'descripcion' => 'Prueba temporal', 'estado' => 'activo',
        'hora_apertura' => '09:00', 'hora_cierre' => '14:00', 'aforo' => 20, 'costo' => 0, 'reglas' => 'Respetar horarios.']);
    $create = fn ($user, $day, $start, $end, $state = 'confirmada') => Reserva::create(['espacio_comun_id' => $space->id,
        'user_id' => $user->id, 'fecha' => $day, 'hora_inicio' => $start, 'hora_fin' => $end, 'estado' => $state]);
    $reservation = $create($users['propio'], $date, '10:00', '11:00');
    $pending = $create($users['propio'], $date, '11:00', '12:00', 'pendiente');
    $begunTime = now()->subMinute();
    $begun = $create($users['propio'], $begunTime->toDateString(), $begunTime->format('H:i:s'), now()->addHour()->format('H:i:s'));
    $past = $create($users['propio'], now()->subDay()->toDateString(), '10:00', '11:00');
    $foreign = $create($users['otro'], $date, '12:00', '13:00');
    $owner = cancelLogin($users['propio']);
    $other = cancelLogin($users['otro']);
    $token = cancelToken($owner, '/perfil');
    $otherToken = cancelToken($other, '/perfil');
    $path = fn ($id) => "/residente/reservas/$id/cancelacion";
    $send = fn ($id, $data) => $owner->patch($path($id), ['headers' => ['Accept' => 'application/json'], 'form_params' => ['_token' => $token] + $data]);
    $snapshot = $reservation->refresh()->getRawOriginal();
    foreach ([[], ['motivo_cancelacion' => ''], ['motivo_cancelacion' => '   '], ['motivo_cancelacion' => ['texto']], ['motivo_cancelacion' => str_repeat('a', 1001)]] as $data) {
        cancelCheck($send($reservation->id, $data)->getStatusCode() === 422 && $snapshot === $reservation->refresh()->getRawOriginal(), 'Justificación inválida rechazada sin modificar reserva');
    }
    $reason = "Cambio de planes familiares.\n<script>alert(1)</script>";
    foreach ([$foreign->id, 2147483647] as $id) {
        cancelCheck($send($id, ['motivo_cancelacion' => $reason])->getStatusCode() === 404, 'Reserva ajena o inexistente no se puede cancelar');
    }
    cancelCheck($owner->patch($path($reservation->id), ['form_params' => ['motivo_cancelacion' => $reason]])->getStatusCode() === 419, 'Cancelación exige CSRF');
    foreach (['Administrador', 'Conserje'] as $role) {
        $client = cancelLogin($users[$role]);
        cancelCheck($client->patch($path($reservation->id), ['headers' => ['Accept' => 'application/json'],
            'form_params' => ['_token' => cancelToken($client, '/perfil'), 'motivo_cancelacion' => $reason]])->getStatusCode() === 403, "$role no puede cancelar la reserva del residente");
    }
    $guest = cancelBrowser();
    cancelCheck($guest->patch($path($reservation->id), ['form_params' => ['_token' => cancelToken($guest, '/login'), 'motivo_cancelacion' => $reason]])->getStatusCode() === 302, 'Invitado no puede cancelar');
    foreach ([$begun, $past] as $item) {
        $before = $item->refresh()->getRawOriginal();
        cancelCheck($send($item->id, ['motivo_cancelacion' => $reason])->getStatusCode() === 422 && $before === $item->refresh()->getRawOriginal(), 'Reserva iniciada o pasada no se puede cancelar');
    }
    $html = (string) $owner->get('/residente/reservas')->getBody();
    cancelCheck(str_contains($html, $path($reservation->id)) && !str_contains($html, $path($begun->id))
        && !str_contains($html, $path($past->id)), 'Solo las reservas propias futuras ofrecen cancelar');
    $response = $send($reservation->id, ['motivo_cancelacion' => $reason, 'user_id' => $users['otro']->id, 'estado' => 'confirmada', 'hora_inicio' => '13:00']);
    $reservation->refresh();
    cancelCheck($response->getStatusCode() === 302 && $reservation->estado === 'cancelada' && $reservation->motivo_cancelacion === $reason
        && $reservation->cancelada_at !== null && $reservation->user_id === $users['propio']->id && $reservation->hora_inicio === '10:00:00', 'Cancela conservando residente y horario, con motivo y fecha de cancelación');
    $cancelled = $reservation->getRawOriginal();
    cancelCheck($send($reservation->id, ['motivo_cancelacion' => 'Otro motivo'])->getStatusCode() === 422
        && $cancelled === $reservation->refresh()->getRawOriginal(), 'Doble envío conserva la primera justificación y fecha');
    $html = (string) $owner->get('/residente/reservas')->getBody();
    cancelCheck(str_contains($html, 'Cancelada el') && str_contains($html, '&lt;script&gt;alert(1)&lt;/script&gt;')
        && !str_contains($html, '<script>alert(1)</script>'), 'Historial muestra cancelación y justificación escapada');
    cancelCheck(!str_contains((string) $other->get('/residente/reservas')->getBody(), 'Cambio de planes familiares'), 'Justificación privada del residente');
    $blocks = app(DisponibilidadEspacio::class)->bloques($space, $date);
    cancelCheck(collect($blocks)->firstWhere('inicio', '10:00')['estado'] === 'disponible', 'Cancelación libera el horario');
    $book = ['_token' => $otherToken, 'espacio_id' => $space->id, 'fecha' => $date, 'bloque' => '10:00–11:00'];
    cancelCheck($other->post('/residente/reservas', ['form_params' => $book])->getStatusCode() === 302, 'Otro residente puede reservar el horario liberado');
    // Cancelar y reservar en paralelo usa el mismo bloqueo de espacio.
    $book['bloque'] = '11:00–12:00';
    $responses = Utils::unwrap([
        $owner->patchAsync($path($pending->id), ['headers' => ['Accept' => 'application/json'], 'form_params' => ['_token' => $token, 'motivo_cancelacion' => 'No podré asistir.']]),
        $other->postAsync('/residente/reservas', ['headers' => ['Accept' => 'application/json'], 'form_params' => $book]),
    ]);
    cancelCheck($responses[0]->getStatusCode() === 302 && in_array($responses[1]->getStatusCode(), [302, 422], true)
        && $pending->refresh()->estado === 'cancelada', 'Cancelación de pendiente y confirmación simultánea se coordinan');
    if ($responses[1]->getStatusCode() === 422) {
        cancelCheck($other->post('/residente/reservas', ['form_params' => $book])->getStatusCode() === 302, 'Si la confirmación llegó primero, puede reintentarse tras cancelar');
    }
    cancelCheck(Reserva::where('espacio_comun_id', $space->id)->whereDate('fecha', $date)->where('hora_inicio', '11:00')->vigentes()->count() === 1, 'Horario liberado tiene una única reserva vigente');
    $users['propio']->update(['estado' => 'inactivo']);
    cancelCheck($send($reservation->id, ['motivo_cancelacion' => $reason])->getStatusCode() === 403, 'Cuenta inactiva no puede cancelar');
    echo "Cancelación de reservas verificada.\n";
} finally {
    if ($space) { Reserva::where('espacio_comun_id', $space->id)->delete(); $space->delete(); }
    foreach ($users as $user) { $user->delete(); }
}
