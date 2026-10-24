<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $error) { fwrite(STDERR, $error->getMessage()."\n"); exit(1); });

use App\Models\Departamento;
use App\Models\GastoComun;
use App\Models\Rol;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Promise\Utils;
use Illuminate\Support\Carbon;

function verify(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
    echo "PASS: $message\n";
}
function browser(): Client
{
    return new Client(['base_uri' => 'http://web', 'cookies' => new CookieJar, 'allow_redirects' => false, 'http_errors' => false, 'timeout' => 20]);
}
function token(Client $client, string $path): string
{
    $response = $client->get($path);
    if ($response->getStatusCode() !== 200 || !preg_match('/name="_token" value="([^"]+)"/', (string) $response->getBody(), $match)) throw new RuntimeException("No se pudo abrir $path");
    return $match[1];
}
function login(User $user): Client
{
    $client = browser();
    $response = $client->post('/login', ['form_params' => ['_token' => token($client, '/login'), 'identifier' => $user->email, 'password' => 'PruebaHU14!123']]);
    verify($response->getStatusCode() === 302 && !str_ends_with($response->getHeaderLine('Location'), '/login'), 'Login temporal');
    return $client;
}

$users = [];
$departamentos = [];
$suffix = bin2hex(random_bytes(6));
$residentPath = '/residente/gastos-comunes';
try {
    foreach (['admin' => 'Administrador', 'r' => 'Residente', 'otro' => 'Residente', 'conserje' => 'Conserje'] as $key => $role) {
        $users[$key] = User::create(['nombre' => "HU14 $key", 'email' => "hu14-$key-$suffix@example.test", 'password' => 'PruebaHU14!123', 'estado' => 'activo', 'rol_id' => Rol::where('nombre', $role)->value('id')]);
    }
    foreach (['r', 'otro'] as $key) {
        $departamentos[$key] = Departamento::create(['numero' => "HU14-$key-$suffix", 'piso' => 1, 'estado' => 'activo']);
        $users[$key]->departamentos()->attach($departamentos[$key]->id);
    }
    $gasto = $departamentos['r']->gastosComunes()->create(['periodo' => '2001-01-01', 'monto' => 85000, 'estado' => 'pendiente']);
    $gasto->detalles()->create(['concepto' => 'Fondo de reserva', 'monto' => 85000]);
    $path = '/admin/gastos-comunes/'.$gasto->id;
    $admin = login($users['admin']);
    $csrf = token($admin, $path.'/estado');
    $residente = login($users['r']);
    $saveDate = fn ($date) => $admin->patch($path.'/vencimiento', ['headers' => ['Accept' => 'application/json'], 'form_params' => ['_token' => $csrf, 'fecha_vencimiento' => $date]]);
    $payment = fn ($changes = []) => $admin->post($path.'/validar-pago', ['headers' => ['Accept' => 'application/json'], 'form_params' => array_replace(['_token' => $csrf, 'monto_recibido' => 85000, 'referencia_pago' => 'Transferencia HU14'], $changes)]);
    verify($gasto->fresh()->estadoVigente() === 'pendiente' && str_contains((string) $residente->get($residentPath)->getBody(), 'Sin fecha definida'), 'Cobros anteriores sin fecha permanecen pendientes');
    foreach (['2026-02-30', 'texto', ['x'], '1899-12-31'] as $date) verify($saveDate($date)->getStatusCode() === 422 && $gasto->fresh()->fecha_vencimiento === null, 'Fecha inválida rechazada sin cambios');
    $today = today('America/Santiago');
    verify($saveDate($today->toDateString())->getStatusCode() === 302 && $gasto->fresh()->estadoVigente() === 'pendiente', 'Vence hoy: sigue pendiente');
    verify(GastoComun::resumenPara($users['r'])['estado'] === 'pendiente', 'Resumen pendiente cuando hay cobros impagos sin vencer');
    verify($saveDate($today->copy()->addDay()->toDateString())->getStatusCode() === 302 && $gasto->fresh()->estadoVigente() === 'pendiente', 'Vencimiento futuro permanece pendiente');
    verify($saveDate(null)->getStatusCode() === 302 && $gasto->fresh()->fecha_vencimiento === null, 'Administrador puede quitar vencimiento de un impago');
    verify($saveDate($today->copy()->subDay()->toDateString())->getStatusCode() === 302 && $gasto->fresh()->estadoVigente() === 'moroso', 'Vencimiento anterior: moroso');
    // Comprueba el límite diario usando explícitamente la zona de Santiago.
    $limite = new GastoComun(['estado' => 'pendiente', 'fecha_vencimiento' => '2026-10-24']);
    Carbon::setTestNow(Carbon::parse('2026-10-24 23:59:59', 'America/Santiago'));
    verify($limite->estadoVigente() === 'pendiente', 'Pendiente hasta el último segundo del vencimiento');
    Carbon::setTestNow(Carbon::parse('2026-10-25 00:00:00', 'America/Santiago'));
    verify($limite->estadoVigente() === 'moroso', 'Moroso desde medianoche de Santiago');
    Carbon::setTestNow();
    for ($i = 1; $i <= 11; $i++) $departamentos['r']->gastosComunes()->create(['periodo' => sprintf('2095-%02d-01', $i), 'monto' => 1000, 'estado' => 'pagado']);
    $html = (string) $residente->get($residentPath)->getBody();
    verify(str_contains($html, 'data-estado-gasto="moroso"') && !str_contains($html, 'data-gasto="'.$gasto->id.'"'), 'Resumen incluye deuda morosa que está fuera de la primera página');
    verify(str_contains((string) $residente->get($residentPath.'?page=2')->getBody(), 'data-estado-gasto="moroso"') && str_contains((string) $residente->get('/residente/dashboard')->getBody(), 'data-estado-gasto="moroso"'), 'Listado y portal muestran morosidad vigente');
    $snapshot = $gasto->fresh()->getAttributes();
    foreach ([['monto_recibido' => 84999], ['monto_recibido' => 85001], ['monto_recibido' => '85000.5'], ['monto_recibido' => ['x']], ['referencia_pago' => ''], ['referencia_pago' => ['x']], ['referencia_pago' => str_repeat('a', 201)]] as $invalid) {
        verify($payment($invalid)->getStatusCode() === 422 && $gasto->fresh()->getAttributes() === $snapshot, 'Pago inválido no cambia estado ni datos de validación');
    }
    verify($admin->post($path.'/validar-pago', ['form_params' => ['monto_recibido' => 85000, 'referencia_pago' => 'Sin token']])->getStatusCode() === 419, 'Validación de pago exige CSRF');
    verify($admin->patch($path.'/vencimiento', ['form_params' => ['fecha_vencimiento' => '2026-01-01']])->getStatusCode() === 419, 'Vencimiento exige CSRF');
    foreach (['r', 'conserje'] as $key) {
        $client = login($users[$key]);
        $userToken = token($client, '/perfil');
        verify($client->get($path.'/estado')->getStatusCode() === 403 && $client->post($path.'/validar-pago', ['form_params' => ['_token' => $userToken, 'monto_recibido' => 85000, 'referencia_pago' => 'No autorizado']])->getStatusCode() === 403
            && $client->patch($path.'/vencimiento', ['form_params' => ['_token' => $userToken, 'fecha_vencimiento' => null]])->getStatusCode() === 403, "$key no administra pagos ni vencimiento");
    }
    // Dos administradores/sesiones no pueden sobrescribir la validación aceptada.
    $fresh = login($users['admin']);
    $csrf2 = token($fresh, $path.'/estado');
    $responses = Utils::unwrap([
        $admin->postAsync($path.'/validar-pago', ['headers' => ['Accept' => 'application/json'], 'form_params' => ['_token' => $csrf, 'monto_recibido' => 85000, 'referencia_pago' => '<script>Referencia A</script>']]),
        $fresh->postAsync($path.'/validar-pago', ['headers' => ['Accept' => 'application/json'], 'form_params' => ['_token' => $csrf2, 'monto_recibido' => 85000, 'referencia_pago' => '<script>Referencia B</script>']]),
    ]);
    $codes = array_map(fn ($r) => $r->getStatusCode(), $responses); sort($codes);
    $paid = $gasto->fresh();
    verify($codes === [302, 422] && $paid->estadoVigente() === 'pagado' && $paid->pago_validado_at && $paid->pago_validado_por === $users['admin']->id, 'Pago validado una sola vez con responsable y fecha');
    verify($paid->monto === 85000 && $paid->detalles()->sum('monto') == 85000 && $paid->departamento_id === $departamentos['r']->id, 'Validación conserva monto, unidad y desglose');
    $html = (string) $admin->get($path.'/estado')->getBody();
    verify(str_contains($html, '&lt;script&gt;Referencia') && !str_contains($html, '<script>Referencia'), 'Referencia de validación escapada');
    verify($payment()->getStatusCode() === 422 && $gasto->fresh()->referencia_pago === $paid->referencia_pago && $saveDate('2099-12-31')->getStatusCode() === 422, 'No se sobrescribe una validación ni el vencimiento de un cobro pagado');
    foreach ([$residentPath, '/residente/dashboard'] as $url) {
        $response = $residente->get($url);
        verify(str_contains((string) $response->getBody(), 'data-estado-gasto="pagado"') && !str_contains((string) $response->getBody(), 'data-estado-gasto="moroso"') && str_contains($response->getHeaderLine('Cache-Control'), 'no-store'), 'Tras validar, listado y portal quedan al día sin caché');
    }
    $otro = login($users['otro']);
    verify(str_contains((string) $otro->get($residentPath)->getBody(), 'Sin cobros registrados'), 'Sin cobros no se presenta un estado de pago ficticio');
    $ajeno = $departamentos['otro']->gastosComunes()->create(['periodo' => '2000-01-01', 'monto' => 1234, 'estado' => 'pendiente', 'fecha_vencimiento' => $today->copy()->subDay()]);
    verify(GastoComun::resumenPara($users['r'])['estado'] === 'pagado' && GastoComun::resumenPara($users['otro'])['estado'] === 'moroso', 'Resumen aislado entre residentes');
    $users['admin']->update(['estado' => 'inactivo']);
    verify($admin->post('/admin/gastos-comunes/'.$ajeno->id.'/validar-pago', ['headers' => ['Accept' => 'application/json'], 'form_params' => ['_token' => $csrf, 'monto_recibido' => 1234, 'referencia_pago' => 'No permitido']])->getStatusCode() === 403 && $ajeno->fresh()->estado === 'pendiente', 'Administrador inactivo no valida pagos');
    verify(browser()->get($path.'/estado')->getStatusCode() === 302, 'Invitado no administra estados');
    echo "HU-14 verificada.\n";
} finally {
    Carbon::setTestNow();
    foreach ($users as $user) $user->departamentos()->detach();
    foreach ($departamentos as $departamento) { $departamento->gastosComunes()->delete(); $departamento->delete(); }
    foreach ($users as $user) $user->delete();
}
