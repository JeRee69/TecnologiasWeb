<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $error) { fwrite(STDERR, $error->getMessage()."\n"); exit(1); });

use App\Models\EspacioComun;
use App\Models\Reserva;
use App\Models\Rol;
use App\Models\User;
use App\Services\DisponibilidadEspacio;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Promise\Utils;

function reviewCheck(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
    echo "PASS: $message\n";
}
function reviewBrowser(): Client
{
    return new Client(['base_uri' => 'http://web', 'cookies' => new CookieJar, 'allow_redirects' => false, 'http_errors' => false, 'timeout' => 20]);
}
function reviewToken(Client $client, string $path): string
{
    $response = $client->get($path);
    if ($response->getStatusCode() !== 200 || !preg_match('/name="_token" value="([^"]+)"/', (string) $response->getBody(), $token)) { throw new RuntimeException('No se pudo obtener CSRF'); }
    return $token[1];
}
function reviewLogin(User $user): Client
{
    $client = reviewBrowser();
    $response = $client->post('/login', ['form_params' => ['_token' => reviewToken($client, '/login'), 'identifier' => $user->email, 'password' => 'RevisionHU!123']]);
    reviewCheck($response->getStatusCode() === 302 && !str_ends_with($response->getHeaderLine('Location'), '/login'), 'Cuenta temporal autenticada');
    return $client;
}

$users = $spaces = [];
$suffix = bin2hex(random_bytes(6));
$date = now()->addDays(5)->toDateString();
$reason = "Solicitud rechazada $suffix. <script>alert(1)</script>";
try {
    foreach (['admin', 'admin2', 'residente', 'otro', 'conserje'] as $type) {
        $role = match ($type) { 'admin', 'admin2' => 'Administrador', 'conserje' => 'Conserje', default => 'Residente' };
        $users[$type] = User::create(['nombre' => "Revisión $type $suffix", 'email' => "revision-$type-$suffix@example.test", 'password' => 'RevisionHU!123',
            'estado' => 'activo', 'rol_id' => Rol::where('nombre', $role)->firstOrFail()->id]);
    }
    foreach (['principal', 'cambiado'] as $type) {
        $spaces[$type] = EspacioComun::create(['nombre' => "Revisión $suffix $type", 'descripcion' => 'Prueba temporal', 'estado' => 'activo',
            'hora_apertura' => '09:00', 'hora_cierre' => '14:00', 'costo' => 0, 'aforo' => 20, 'reglas' => 'Respetar horarios.']);
    }
    $resident = reviewLogin($users['residente']);
    $admin = reviewLogin($users['admin']);
    $admin2 = reviewLogin($users['admin2']);
    $residentToken = reviewToken($resident, '/perfil');
    $adminToken = reviewToken($admin, '/perfil');
    $admin2Token = reviewToken($admin2, '/perfil');
    $create = fn ($start, $end, $day = null, $space = null) => Reserva::create(['espacio_comun_id' => ($space ?? $spaces['principal'])->id,
        'user_id' => $users['residente']->id, 'fecha' => $day ?? $date, 'hora_inicio' => $start, 'hora_fin' => $end, 'estado' => 'pendiente']);
    $path = fn ($id) => "/admin/reservas/$id/estado";
    $send = fn ($id, $data) => $admin->patch($path($id), ['headers' => ['Accept' => 'application/json'], 'form_params' => ['_token' => $adminToken] + $data]);
    $response = $resident->post('/residente/reservas', ['form_params' => ['_token' => $residentToken, 'espacio_id' => $spaces['principal']->id,
        'fecha' => $date, 'bloque' => '09:00–10:00', 'estado' => 'confirmada', 'user_id' => $users['otro']->id]]);
    $request = Reserva::where('espacio_comun_id', $spaces['principal']->id)->firstOrFail();
    reviewCheck($response->getStatusCode() === 302 && $request->estado === 'pendiente' && $request->user_id === $users['residente']->id
        && $request->eventos()->count() === 1, 'Solicitud del residente queda pendiente pese a manipular estado o titular');
    $html = (string) $admin->get('/admin/reservas', ['query' => ['estado' => 'pendiente', 'espacio_id' => $spaces['principal']->id]])->getBody();
    reviewCheck(str_contains($html, $path($request->id)) && str_contains($html, 'Aprobar solicitud') && str_contains($html, 'Rechazar solicitud'), 'Pendientes ofrecen aprobar o rechazar');
    foreach ([[], ['estado' => 'pendiente'], ['estado' => ['confirmada']], ['estado' => 'rechazada'], ['estado' => 'rechazada', 'observacion' => '   '],
        ['estado' => 'confirmada', 'observacion' => ['texto']], ['estado' => 'confirmada', 'observacion' => str_repeat('a', 1001)]] as $invalid) {
        reviewCheck($send($request->id, $invalid)->getStatusCode() === 422 && $request->refresh()->estado === 'pendiente', 'Decisión o justificación inválida no cambia el estado');
    }
    reviewCheck($admin->patch($path($request->id), ['form_params' => ['estado' => 'confirmada']])->getStatusCode() === 419, 'Revisión exige CSRF');
    reviewCheck($send($request->id, ['estado' => 'confirmada', 'observacion' => 'Aprobado por administración.', 'user_id' => $users['otro']->id])->getStatusCode() === 302
        && $request->refresh()->estado === 'confirmada' && $request->user_id === $users['residente']->id, 'Administrador aprueba pendiente sin cambiar titular');
    $event = $request->eventos()->first();
    reviewCheck($event->user_id === $users['admin']->id && $event->estado_anterior === 'pendiente' && $event->estado_nuevo === 'confirmada', 'Historial registra responsable y transición');
    reviewCheck($send($request->id, ['estado' => 'confirmada'])->getStatusCode() === 422 && $request->eventos()->count() === 2, 'Doble aprobación no duplica eventos');
    reviewCheck($send($request->id, ['estado' => 'rechazada', 'observacion' => $reason])->getStatusCode() === 422, 'No permite rechazar una reserva ya aprobada');
    reviewCheck($send($request->id, ['estado' => 'cancelada'])->getStatusCode() === 422, 'Cancelación administrativa requiere motivo');
    reviewCheck($send($request->id, ['estado' => 'cancelada', 'observacion' => 'Mantenimiento del espacio.'])->getStatusCode() === 302
        && $request->refresh()->estado === 'cancelada' && $request->motivo_cancelacion === 'Mantenimiento del espacio.' && $request->cancelada_at !== null
        && $request->eventos()->count() === 3, 'Administrador cancela aprobada y conserva historial de solicitud y aprobación');
    $rejected = $create('10:00', '11:00');
    reviewCheck($send($rejected->id, ['estado' => 'rechazada', 'observacion' => $reason])->getStatusCode() === 302 && $rejected->refresh()->estado === 'rechazada', 'Administrador rechaza pendiente con justificación');
    reviewCheck(collect(app(DisponibilidadEspacio::class)->bloques($spaces['principal'], $date))->firstWhere('inicio', '10:00')['estado'] === 'disponible', 'Rechazo libera el horario');
    $html = (string) $resident->get('/residente/reservas')->getBody();
    reviewCheck(str_contains($html, 'Rechazada') && str_contains($html, "Solicitud rechazada $suffix") && str_contains($html, '&lt;script&gt;alert(1)&lt;/script&gt;')
        && !str_contains($html, '<script>alert(1)</script>'), 'Residente ve decisión e historial con observación escapada');
    reviewCheck($send($rejected->id, ['estado' => 'confirmada'])->getStatusCode() === 422, 'Rechazada no puede reabrirse');
    $changed = $create('11:00', '12:00', null, $spaces['cambiado']);
    $spaces['cambiado']->update(['estado' => 'inactivo']);
    reviewCheck($send($changed->id, ['estado' => 'confirmada'])->getStatusCode() === 422, 'No aprueba espacio desactivado después de solicitar');
    $spaces['cambiado']->update(['estado' => 'activo', 'hora_cierre' => '10:30']);
    reviewCheck($send($changed->id, ['estado' => 'confirmada'])->getStatusCode() === 422, 'No aprueba bloque fuera de la configuración actual');
    $spaces['cambiado']->update(['hora_cierre' => '14:00']);
    $users['residente']->update(['estado' => 'inactivo']);
    reviewCheck($send($changed->id, ['estado' => 'confirmada'])->getStatusCode() === 422, 'No aprueba solicitud de residente inactivo');
    $users['residente']->update(['estado' => 'activo']);
    $past = $create('11:00', '12:00', now()->subDay()->toDateString());
    reviewCheck($send($past->id, ['estado' => 'confirmada'])->getStatusCode() === 422, 'No aprueba horario pasado');
    reviewCheck($send($past->id, ['estado' => 'rechazada', 'observacion' => 'Solicitud vencida.'])->getStatusCode() === 302, 'Permite cerrar pendiente vencida mediante rechazo');
    $overlap = $create('11:30', '12:30', null, $spaces['cambiado']);
    reviewCheck($send($changed->id, ['estado' => 'confirmada'])->getStatusCode() === 422 && $changed->refresh()->estado === 'pendiente', 'Aprobación detecta otro registro parcialmente solapado');
    $overlap->delete();
    // Dos administradores deciden sobre la misma solicitud: solo la primera decisión se guarda.
    $race = $create('12:00', '13:00');
    $responses = Utils::unwrap([
        $admin->patchAsync($path($race->id), ['headers' => ['Accept' => 'application/json'], 'form_params' => ['_token' => $adminToken, 'estado' => 'confirmada']]),
        $admin2->patchAsync($path($race->id), ['headers' => ['Accept' => 'application/json'], 'form_params' => ['_token' => $admin2Token, 'estado' => 'rechazada', 'observacion' => 'No autorizado.']]),
    ]);
    $statuses = array_map(fn ($response) => $response->getStatusCode(), $responses);
    sort($statuses);
    reviewCheck($statuses === [302, 422] && $race->eventos()->count() === 1, 'Decisiones simultáneas no se pisan ni duplican eventos');
    foreach (['otro', 'conserje'] as $type) {
        $client = reviewLogin($users[$type]);
        reviewCheck($client->patch($path($changed->id), ['headers' => ['Accept' => 'application/json'], 'form_params' => ['_token' => reviewToken($client, '/perfil'), 'estado' => 'confirmada']])->getStatusCode() === 403,
            "$type no puede gestionar solicitudes");
    }
    $users['admin']->update(['estado' => 'inactivo']);
    reviewCheck($send($changed->id, ['estado' => 'confirmada'])->getStatusCode() === 403, 'Administrador desactivado no puede decidir');
    $guest = reviewBrowser();
    reviewCheck($guest->patch($path($changed->id), ['form_params' => ['_token' => reviewToken($guest, '/login'), 'estado' => 'confirmada']])->getStatusCode() === 302, 'Invitado no puede gestionar solicitudes');
    echo "Revisión administrativa de solicitudes verificada.\n";
} finally {
    Reserva::whereIn('espacio_comun_id', array_map(fn ($space) => $space->id, $spaces))->delete();
    foreach ($spaces as $space) { $space->delete(); }
    foreach ($users as $user) { $user->delete(); }
}
