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
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Promise\Utils;

function reservationCheck(bool $condition, string $message): void
{
    if (! $condition) { throw new RuntimeException($message); }
    echo "PASS: $message\n";
}

function reservationBrowser(): Client
{
    return new Client(['base_uri' => 'http://web', 'cookies' => new CookieJar,
        'allow_redirects' => false, 'http_errors' => false, 'timeout' => 20]);
}

function reservationToken(Client $client, string $path): string
{
    $response = $client->get($path);
    if ($response->getStatusCode() !== 200 || ! preg_match('/name="_token" value="([^"]+)"/', (string) $response->getBody(), $token)) {
        throw new RuntimeException("No se pudo obtener CSRF de $path");
    }
    return $token[1];
}

function reservationLogin(User $user): Client
{
    $client = reservationBrowser();
    $response = $client->post('/login', ['form_params' => ['_token' => reservationToken($client, '/login'),
        'identifier' => $user->email, 'password' => 'PruebaHU22!123']]);
    reservationCheck($response->getStatusCode() === 302 && !str_ends_with($response->getHeaderLine('Location'), '/login'), 'Cuenta temporal autenticada');
    return $client;
}

$users = $spaces = [];
$suffix = bin2hex(random_bytes(6));
$path = '/residente/reservas';
$calendar = '/residente/espacios-comunes/disponibilidad';
$date = now()->addDays(3)->toDateString();
try {
    foreach (['Administrador', 'Conserje', 'Residente', 'Otro'] as $role) {
        $users[$role] = User::create(['nombre' => "HU22 $role", 'email' => "hu22-$role-$suffix@example.test", 'password' => 'PruebaHU22!123',
            'rol_id' => Rol::where('nombre', $role === 'Otro' ? 'Residente' : $role)->firstOrFail()->id, 'estado' => 'activo']);
    }
    foreach (['principal', 'otro', 'inactivo', 'pendiente'] as $type) {
        $spaces[$type] = EspacioComun::create(['nombre' => "HU22 $suffix $type", 'descripcion' => 'Prueba de reservas',
            'estado' => $type === 'inactivo' ? 'inactivo' : 'activo'] + ($type === 'pendiente' ? [] : [
                'aforo' => 20, 'hora_apertura' => '09:15', 'hora_cierre' => '13:45', 'costo' => 0, 'reglas' => 'Respetar horario.']));
    }
    $resident = reservationLogin($users['Residente']);
    $other = reservationLogin($users['Otro']);
    $token = reservationToken($resident, '/perfil');
    $otherToken = reservationToken($other, '/perfil');
    $valid = ['espacio_id' => $spaces['principal']->id, 'fecha' => $date, 'bloque' => '09:15–10:15'];
    $count = fn () => Reserva::whereIn('espacio_comun_id', array_map(fn ($space) => $space->id, $spaces))->count();
    $send = fn ($data) => $resident->post($path, ['headers' => ['Accept' => 'application/json'], 'form_params' => ['_token' => $token] + $data]);
    reservationCheck(str_contains((string) $resident->get($path)->getBody(), 'Aún no tienes reservas'), 'Listado comienza vacío');
    $response = $send($valid + ['user_id' => $users['Otro']->id, 'estado' => 'cancelada', 'hora_fin' => '23:00']);
    reservationCheck($response->getStatusCode() === 302 && str_ends_with($response->getHeaderLine('Location'), $path), 'Envío de solicitud redirige a Mis reservas');
    $reservation = Reserva::where('espacio_comun_id', $spaces['principal']->id)->firstOrFail();
    reservationCheck($reservation->user_id === $users['Residente']->id && $reservation->estado === 'pendiente'
        && $reservation->hora_inicio === '09:15:00' && $reservation->hora_fin === '10:15:00', 'Guarda residente autenticado y horario validado, ignorando campos ajenos');
    $html = (string) $resident->get($path)->getBody();
    reservationCheck(str_contains($html, 'Queda pendiente de aprobación') && str_contains($html, '#'.$reservation->id)
        && str_contains($html, $spaces['principal']->nombre) && str_contains($html, 'Pendiente'), 'Listado personal muestra solicitud pendiente persistida');
    reservationCheck(!str_contains((string) $other->get($path, ['query' => ['user_id' => $users['Residente']->id]])->getBody(), $spaces['principal']->nombre), 'Otro residente no puede consultar reservas ajenas ni manipulando user_id');
    foreach ([[], ['bloque' => ''], ['bloque' => ['09:15–10:15']], ['bloque' => '09:30–10:30'], ['bloque' => '08:15–09:15'],
        ['bloque' => '13:15–14:15'], ['bloque' => '12:15–11:15'], ['fecha' => '2026-02-30'], ['fecha' => ['2026-10-20']],
        ['fecha' => now()->subDay()->toDateString()], ['espacio_id' => ['1']], ['espacio_id' => 2147483647],
        ['espacio_id' => $spaces['inactivo']->id], ['espacio_id' => $spaces['pendiente']->id],
    ] as $index => $invalid) {
        $before = $count();
        reservationCheck($send($index === 0 ? [] : array_merge($valid, $invalid))->getStatusCode() === 422 && $count() === $before,
            'Horario o filtro inválido se rechaza sin crear reservas');
    }
    reservationCheck($send($valid)->getStatusCode() === 422 && $count() === 1, 'Reenviar el formulario no duplica una reserva');
    $resident->post($path, ['headers' => ['Referer' => 'http://web'.$calendar.'?'.http_build_query(['espacio_id' => $spaces['principal']->id, 'fecha' => $date])],
        'form_params' => ['_token' => $token] + $valid]);
    $html = (string) $resident->get($calendar, ['query' => ['espacio_id' => $spaces['principal']->id, 'fecha' => $date]])->getBody();
    reservationCheck(str_contains($html, 'ya no está disponible') && str_contains($html, 'Enviar solicitud'), 'Conflicto se explica en calendario y permite elegir otro horario');
    reservationCheck($send(array_merge($valid, ['bloque' => '10:15–11:15']))->getStatusCode() === 302, 'Reserva contigua sin solapamiento se permite');
    reservationCheck($send(array_merge($valid, ['bloque' => '13:15–13:45']))->getStatusCode() === 302, 'Permite el bloque final corto');
    // Dos sesiones distintas compiten por el mismo bloque, sin reservas previas en ese espacio.
    $race = array_merge($valid, ['espacio_id' => $spaces['otro']->id]);
    $responses = Utils::unwrap([
        $resident->postAsync($path, ['headers' => ['Accept' => 'application/json'], 'form_params' => ['_token' => $token] + $race]),
        $other->postAsync($path, ['headers' => ['Accept' => 'application/json'], 'form_params' => ['_token' => $otherToken] + $race]),
    ]);
    $statuses = array_map(fn ($response) => $response->getStatusCode(), $responses);
    sort($statuses);
    reservationCheck($statuses === [302, 422] && Reserva::where('espacio_comun_id', $spaces['otro']->id)->count() === 1, 'Dos solicitudes concurrentes producen una única pendiente');
    reservationCheck($resident->post($path, ['form_params' => $valid])->getStatusCode() === 419, 'Confirmación requiere CSRF');
    foreach (['Administrador', 'Conserje'] as $role) {
        $client = reservationLogin($users[$role]);
        $csrf = reservationToken($client, '/perfil');
        reservationCheck($client->get($path)->getStatusCode() === 403 && $client->post($path, ['form_params' => ['_token' => $csrf] + $valid])->getStatusCode() === 403,
            "$role no puede crear ni consultar reservas de residentes");
    }
    $users['Residente']->update(['estado' => 'inactivo']);
    reservationCheck($send($valid)->getStatusCode() === 403, 'Residente inactivo no puede confirmar');
    $guest = reservationBrowser();
    reservationCheck($guest->get($path)->getStatusCode() === 302 && $guest->post($path, ['form_params' => ['_token' => reservationToken($guest, '/login')] + $valid])->getStatusCode() === 302,
        'Invitado no puede listar ni confirmar reservas');
    echo "HU-22: reserva de espacios comunes verificada.\n";
} finally {
    Reserva::whereIn('espacio_comun_id', array_map(fn ($space) => $space->id, $spaces))->delete();
    foreach ($spaces as $space) { $space->delete(); }
    foreach ($users as $user) { $user->delete(); }
}
