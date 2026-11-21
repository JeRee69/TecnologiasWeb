<?php

// Recuperación web/API con el transporte Resend real y HTTP del proveedor simulado.
// No requiere API key real ni envía correos externos. Usa cuentas temporales.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$app->instance('env', 'testing');
set_exception_handler(function (Throwable $error) {
    fwrite(STDERR, $error->getMessage()."\n");
    exit(1);
});

use App\Models\Rol;
use App\Models\User;
use App\Services\RecuperacionContrasena;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Illuminate\Mail\Transport\ResendTransport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

function recoveryCheck(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
    echo "PASS: $message\n";
}

function recoveryRequest(string $path, string $method = 'GET', array $data = [], ?string $ip = null): \Symfony\Component\HttpFoundation\Response
{
    global $app, $ipPrefix;
    static $sequence = 0;
    $request = Request::create('https://host-no-confiable.example'.$path, $method, $data, [], [], [
        'HTTP_ACCEPT' => 'application/json', 'REMOTE_ADDR' => $ip ?? $ipPrefix.(++$sequence),
    ]);
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle($request);
    $kernel->terminate($request, $response);
    return $response;
}

$user = null;
$history = [];
$mock = new MockHandler;
$handler = HandlerStack::create($mock);
$handler->push(Middleware::history($history));
$suffix = bin2hex(random_bytes(6));
$ipPrefix = '198.19.'.random_int(1, 254).'.';
$originalUrl = config('app.url');
config(['app.url' => 'https://comunidad.example', 'mail.default' => 'resend',
    'mail.from.address' => 'no-reply@comunidad.example', 'services.resend.key' => 're_test_no_real']);

try {
    $user = User::create(['nombre' => '<script>Residente</script>', 'email' => "recuperacion-$suffix@example.test",
        'password' => 'AnteriorHU38!123', 'estado' => 'activo', 'rol_id' => Rol::where('nombre', 'Residente')->value('id')]);
    recoveryCheck(Mail::mailer()->getSymfonyTransport() instanceof ResendTransport, 'Configuración resuelve el transporte API nativo de Resend');
    $resend = new Resend\Client(new Resend\Transporters\HttpTransporter(
        new Client(['handler' => $handler]),
        Resend\ValueObjects\Transporter\BaseUri::from('api.resend.com'),
        Resend\ValueObjects\Transporter\Headers::withAuthorization(Resend\ValueObjects\ApiKey::from('re_test_no_real')),
    ));
    Mail::mailer()->setSymfonyTransport(new ResendTransport($resend));

    $mock->append(new Response(200, ['Content-Type' => 'application/json'], '{"id":"email-web-test"}'));
    $response = recoveryRequest('/recuperar-contrasena', 'POST', ['email' => $user->email]);
    recoveryCheck($response->getStatusCode() === 302 && count($history) === 1, 'Solicitud web envía el correo mediante la API de Resend');
    $payload = json_decode((string) $history[0]['request']->getBody(), true);
    recoveryCheck((string) $history[0]['request']->getUri() === 'https://api.resend.com/emails'
        && $history[0]['request']->getMethod() === 'POST', 'Envío utiliza POST /emails de Resend');
    recoveryCheck($history[0]['request']->getHeaderLine('Authorization') === 'Bearer re_test_no_real', 'SDK autentica la solicitud mediante API key');
    recoveryCheck($payload['to'] === [$user->email] && str_contains($payload['from'], 'no-reply@comunidad.example'), 'Correo usa destinatario de la cuenta y remitente configurado');
    recoveryCheck(str_contains($payload['html'], '60 minutos') && !str_contains($payload['html'], '<script>'), 'Correo incluye vigencia y escapa el nombre del residente');
    preg_match('~https://comunidad\.example/restablecer-contrasena/[^\s]+~', $payload['text'], $matches);
    $url = $matches[0] ?? '';
    parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $query);
    $token = basename(parse_url($url, PHP_URL_PATH) ?? '');
    recoveryCheck(strlen($token) === 64 && ($query['email'] ?? null) === $user->email
        && !str_contains($url, 'host-no-confiable'), 'Enlace usa APP_URL y los datos correctos, sin confiar en el Host de la solicitud');
    $stored = DB::table('password_reset_tokens')->where('email', $user->email)->first();
    recoveryCheck($stored && $stored->token !== $token && Hash::check($token, $stored->token), 'Token se almacena como hash');
    $page = recoveryRequest('/recuperar-contrasena');
    recoveryCheck(!str_contains($page->getContent(), $token) && !str_contains($page->getContent(), 'Enlace generado para pruebas'), 'Página de solicitud no revela el enlace ni el token');

    $response = recoveryRequest('/api/v1/auth/forgot-password', 'POST', ['email' => $user->email]);
    recoveryCheck($response->getStatusCode() === 200 && count($history) === 1, 'Web y API comparten espera de 60 segundos sin invalidar el enlace recién enviado');
    $knownMessage = json_decode($response->getContent(), true)['message'];
    $response = recoveryRequest('/api/v1/auth/forgot-password', 'POST', ['email' => "inexistente-$suffix@example.test"]);
    recoveryCheck($response->getStatusCode() === 200 && json_decode($response->getContent(), true)['message'] === $knownMessage
        && count($history) === 1, 'Correo no registrado recibe el mismo mensaje y no produce envíos');
    recoveryCheck(recoveryRequest('/api/v1/auth/forgot-password', 'POST', ['email' => 'invalido'])->getStatusCode() === 422, 'API rechaza formato de correo inválido');

    recoveryCheck(recoveryRequest('/restablecer-contrasena/'.$token, 'GET', ['email' => $user->email])->getStatusCode() === 200, 'Enlace enviado permite abrir el formulario de restablecimiento');
    recoveryCheck(recoveryRequest('/restablecer-contrasena/'.str_repeat('z', 64), 'GET', ['email' => $user->email])->getStatusCode() === 302, 'Un token incorrecto no abre el formulario');
    recoveryCheck(recoveryRequest('/restablecer-contrasena/'.$token, 'GET', ['email' => ['malformado']])->getStatusCode() === 302, 'Correo malformado en el enlace se rechaza sin error 500');

    DB::table('password_reset_tokens')->where('email', $user->email)->update(['created_at' => now()->subMinutes(2)]);
    $mock->append(new Response(422, ['Content-Type' => 'application/json'], '{"name":"validation_error","message":"Rejected sender"}'));
    $response = recoveryRequest('/api/v1/auth/forgot-password', 'POST', ['email' => $user->email]);
    recoveryCheck($response->getStatusCode() === 422 && !str_contains($response->getContent(), 'Rejected sender'), 'Fallo de Resend devuelve error controlado sin fingir envío ni exponer detalles');
    recoveryCheck(Hash::check($token, DB::table('password_reset_tokens')->where('email', $user->email)->value('token')), 'Fallo del proveedor conserva el enlace anterior');

    $mock->append(new Response(200, ['Content-Type' => 'application/json'], '{"id":"email-api-test"}'));
    $response = recoveryRequest('/api/v1/auth/forgot-password', 'POST', ['email' => $user->email]);
    recoveryCheck($response->getStatusCode() === 200 && count($history) === 3, 'Solicitud API envía el correo al terminar la espera');
    $payload = json_decode((string) $history[2]['request']->getBody(), true);
    preg_match('~https://comunidad\.example/restablecer-contrasena/[^\s]+~', $payload['text'], $matches);
    $nuevoToken = basename(parse_url($matches[0], PHP_URL_PATH));
    recoveryCheck(!Hash::check($token, DB::table('password_reset_tokens')->where('email', $user->email)->value('token')),
        'Nuevo envío exitoso invalida el enlace anterior');
    DB::table('password_reset_tokens')->where('email', $user->email)->update(['created_at' => now()->subMinutes(61)]);
    recoveryCheck(recoveryRequest('/restablecer-contrasena/'.$nuevoToken, 'GET', ['email' => $user->email])->getStatusCode() === 302, 'Token de más de 60 minutos no abre el formulario');
    $reset = ['token' => $nuevoToken, 'email' => $user->email, 'password' => 'NuevaHU38!123456', 'password_confirmation' => 'NuevaHU38!123456'];
    recoveryCheck(recoveryRequest('/restablecer-contrasena', 'POST', $reset)->getStatusCode() === 422, 'Token vencido tampoco cambia la contraseña');
    DB::table('password_reset_tokens')->where('email', $user->email)->update(['created_at' => now()]);
    $user->createToken('hu38-temporal');
    recoveryCheck(recoveryRequest('/restablecer-contrasena', 'POST', $reset)->getStatusCode() === 302
        && Hash::check($reset['password'], $user->fresh()->password), 'Token recibido por correo permite establecer una nueva contraseña');
    recoveryCheck(!$user->tokens()->exists() && !DB::table('password_reset_tokens')->where('email', $user->email)->exists(), 'Restablecimiento consume el token y revoca accesos API anteriores');
    recoveryCheck(recoveryRequest('/restablecer-contrasena', 'POST', $reset)->getStatusCode() === 422, 'Token utilizado no puede reutilizarse');

    config(['services.resend.key' => null]);
    app('mail.manager')->purge('resend');
    $response = recoveryRequest('/api/v1/auth/forgot-password', 'POST', ['email' => $user->email]);
    recoveryCheck($response->getStatusCode() === 422 && !DB::table('password_reset_tokens')->where('email', $user->email)->exists(), 'API key ausente produce error controlado y no deja un token sin enviar');
    for ($i = 0; $i < 6; $i++) {
        $response = recoveryRequest('/api/v1/auth/forgot-password', 'POST', ['email' => "inexistente-$suffix@example.test"], $ipPrefix.'250');
    }
    recoveryCheck($response->getStatusCode() === 429, 'Recuperación limita solicitudes por IP');
    recoveryCheck(recoveryRequest('/recuperar-contrasena', 'POST', ['email' => "inexistente-$suffix@example.test"], $ipPrefix.'250')->getStatusCode() === 429,
        'Límite de solicitudes se comparte entre web y API');
    echo "HU-38: recuperación con Resend verificada sin envíos externos.\n";
} finally {
    if ($user) {
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        $user->tokens()->delete();
        $user->delete();
    }
    config(['app.url' => $originalUrl]);
}
