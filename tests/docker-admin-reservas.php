<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $error) { fwrite(STDERR, $error->getMessage()."\n"); exit(1); });

use App\Models\EspacioComun;
use App\Models\Reserva;
use App\Models\Rol;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

function adminBookingCheck(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
    echo "PASS: $message\n";
}
function adminBookingBrowser(): Client
{
    return new Client(['base_uri' => 'http://web', 'cookies' => new CookieJar, 'allow_redirects' => false, 'http_errors' => false, 'timeout' => 20]);
}
function adminBookingToken(Client $client, string $path): string
{
    $response = $client->get($path);
    if ($response->getStatusCode() !== 200 || !preg_match('/name="_token" value="([^"]+)"/', (string) $response->getBody(), $token)) {
        throw new RuntimeException('No se pudo obtener CSRF');
    }
    return $token[1];
}
function adminBookingLogin(User $user): Client
{
    $client = adminBookingBrowser();
    $response = $client->post('/login', ['form_params' => ['_token' => adminBookingToken($client, '/login'), 'identifier' => $user->email, 'password' => 'AdminReservas!123']]);
    adminBookingCheck($response->getStatusCode() === 302 && !str_ends_with($response->getHeaderLine('Location'), '/login'), 'Cuenta temporal autenticada');
    return $client;
}
function adminBookingIds(string $html): array
{
    $document = new DOMDocument;
    $previous = libxml_use_internal_errors(true);
    $document->loadHTML('<?xml encoding="UTF-8">'.$html);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    $ids = [];
    foreach ((new DOMXPath($document))->query('//tbody/tr/td[1]') as $cell) { $ids[] = (int) ltrim(trim($cell->textContent), '#'); }
    return $ids;
}

$users = $spaces = [];
$suffix = bin2hex(random_bytes(6));
$path = '/admin/reservas';
$date = now()->addDays(5)->toDateString();
try {
    foreach (['Administrador', 'Conserje', 'Residente', 'Otro'] as $type) {
        $users[$type] = User::create(['nombre' => "Reservas $type $suffix", 'email' => "admin-reservas-$type-$suffix@example.test", 'password' => 'AdminReservas!123',
            'estado' => 'activo', 'rol_id' => Rol::where('nombre', $type === 'Otro' ? 'Residente' : $type)->firstOrFail()->id]);
    }
    foreach (['principal', 'otro'] as $type) {
        $spaces[$type] = EspacioComun::create(['nombre' => "Admin reservas $suffix $type", 'descripcion' => 'Prueba temporal', 'estado' => 'activo',
            'aforo' => 20, 'hora_apertura' => '09:00', 'hora_cierre' => '14:00', 'costo' => 0, 'reglas' => 'Respetar horario.']);
    }
    $ids = [];
    for ($i = 0; $i < 12; $i++) {
        $item = Reserva::create(['espacio_comun_id' => $spaces['principal']->id, 'user_id' => $users[$i % 2 ? 'Residente' : 'Otro']->id,
            'fecha' => $date, 'hora_inicio' => '09:00', 'hora_fin' => '10:00', 'estado' => 'cancelada']);
        if ($i !== 0) {
            $item->motivo_cancelacion = "No podré asistir $suffix. <script>alert(1)</script>";
            $item->cancelada_at = now();
            $item->save();
        }
        $ids[] = $item->id;
    }
    $ids = array_reverse($ids);
    $confirmed = Reserva::create(['espacio_comun_id' => $spaces['otro']->id, 'user_id' => $users['Residente']->id,
        'fecha' => $date, 'hora_inicio' => '10:00', 'hora_fin' => '11:00', 'estado' => 'confirmada']);
    $pending = Reserva::create(['espacio_comun_id' => $spaces['otro']->id, 'user_id' => $users['Otro']->id,
        'fecha' => now()->addDays(6)->toDateString(), 'hora_inicio' => '11:00', 'hora_fin' => '12:00', 'estado' => 'pendiente']);
    // El historial de un espacio sigue visible después de desactivarlo.
    $spaces['principal']->update(['estado' => 'inactivo']);
    $admin = adminBookingLogin($users['Administrador']);
    adminBookingCheck(str_contains((string) $admin->get('/admin/dashboard')->getBody(), $path), 'Panel del administrador enlaza a reservas y cancelaciones');
    $query = ['espacio_id' => $spaces['principal']->id, 'fecha' => $date, 'estado' => 'cancelada'];
    $all = [];
    foreach ([1, 2] as $page) {
        $response = $admin->get($path, ['query' => $query + ['page' => $page]]);
        $html = (string) $response->getBody();
        adminBookingCheck($response->getStatusCode() === 200 && str_contains($response->getHeaderLine('Cache-Control'), 'no-store'), "Consulta privada sin caché, página $page");
        $found = adminBookingIds($html);
        adminBookingCheck($found === array_slice($ids, ($page - 1) * 10, 10), "Filtros y orden correctos en página $page");
        adminBookingCheck(str_contains($html, $users['Residente']->email) && str_contains($html, $users['Otro']->email), 'Administrador ve reservas de ambos residentes');
        adminBookingCheck(str_contains($html, '&lt;script&gt;alert(1)&lt;/script&gt;') && !str_contains($html, '<script>alert(1)</script>'), 'Justificación visible con texto escapado');
        adminBookingCheck(str_contains($html, 'espacio_id='.$spaces['principal']->id) && str_contains($html, 'estado=cancelada')
            && str_contains($html, 'fecha='.$date), 'Paginación conserva los filtros');
        $all = array_merge($all, $found);
        if ($page === 2) { adminBookingCheck(str_contains($html, 'Sin justificación registrada.'), 'Cancelaciones antiguas sin motivo se muestran correctamente'); }
    }
    adminBookingCheck($all === $ids, 'Historial incluye todas las cancelaciones del espacio inactivo');
    foreach (['confirmada' => $confirmed, 'pendiente' => $pending] as $state => $item) {
        $html = (string) $admin->get($path, ['query' => ['espacio_id' => $spaces['otro']->id, 'estado' => $state]])->getBody();
        adminBookingCheck(adminBookingIds($html) === [$item->id] && str_contains($html, $item->fecha->format('d/m/Y'))
            && str_contains($html, substr($item->hora_inicio, 0, 5).'–'.substr($item->hora_fin, 0, 5)), "Consulta muestra fecha y horario del estado $state");
    }
    $html = (string) $admin->get($path, ['query' => $query + ['page' => 99]])->getBody();
    adminBookingCheck(str_contains($html, 'Volver a la primera página'), 'Página fuera de rango permite volver');
    $html = (string) $admin->get($path, ['query' => array_merge($query, ['estado' => 'confirmada'])])->getBody();
    adminBookingCheck(str_contains($html, 'No hay reservas que coincidan'), 'Filtros sin resultados muestran aviso');
    foreach ([['fecha' => '2026-02-30'], ['fecha' => ['2026-10-20']], ['espacio_id' => ['1']], ['espacio_id' => 2147483647], ['estado' => ['cancelada']], ['estado' => 'otro']] as $invalid) {
        adminBookingCheck($admin->get($path, ['query' => $invalid])->getStatusCode() === 422, 'Filtro inválido se rechaza sin error de servidor');
    }
    $resident = adminBookingLogin($users['Residente']);
    $reason = "Cambio de planes $suffix";
    $response = $resident->patch("/residente/reservas/$confirmed->id/cancelacion", ['form_params' => ['_token' => adminBookingToken($resident, '/perfil'), 'motivo_cancelacion' => $reason]]);
    $html = (string) $admin->get($path, ['query' => ['espacio_id' => $spaces['otro']->id, 'estado' => 'cancelada']])->getBody();
    adminBookingCheck($response->getStatusCode() === 302 && adminBookingIds($html) === [$confirmed->id]
        && str_contains($html, $reason) && str_contains($html, $confirmed->refresh()->cancelada_at->format('d/m/Y H:i')), 'Cancelación real del residente aparece al administrador con motivo y fecha');
    foreach (['Residente', 'Conserje'] as $role) {
        $client = $role === 'Residente' ? $resident : adminBookingLogin($users[$role]);
        adminBookingCheck($client->get($path)->getStatusCode() === 403, "$role no accede al historial de toda la comunidad");
    }
    $users['Administrador']->update(['estado' => 'inactivo']);
    adminBookingCheck($admin->get($path)->getStatusCode() === 302, 'Administrador desactivado pierde acceso');
    adminBookingCheck(adminBookingBrowser()->get($path)->getStatusCode() === 302, 'Invitado debe iniciar sesión');
    echo "Consulta administrativa de reservas y cancelaciones verificada.\n";
} finally {
    Reserva::whereIn('espacio_comun_id', array_map(fn ($space) => $space->id, $spaces))->delete();
    foreach ($spaces as $space) { $space->delete(); }
    foreach ($users as $user) { $user->delete(); }
}
