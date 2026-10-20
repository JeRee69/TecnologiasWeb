<?php

// Consulta personal: contenido de las filas, aislamiento, historial y paginación.
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

function personalCheck(bool $condition, string $message): void
{
    if (! $condition) { throw new RuntimeException($message); }
    echo "PASS: $message\n";
}

function personalBrowser(): Client
{
    return new Client(['base_uri' => 'http://web', 'cookies' => new CookieJar,
        'allow_redirects' => false, 'http_errors' => false, 'timeout' => 20]);
}

function personalLogin(User $user): Client
{
    $client = personalBrowser();
    preg_match('/name="_token" value="([^"]+)"/', (string) $client->get('/login')->getBody(), $token);
    $response = $client->post('/login', ['form_params' => ['_token' => $token[1], 'identifier' => $user->email, 'password' => 'PruebaHU24!123']]);
    personalCheck($response->getStatusCode() === 302 && !str_ends_with($response->getHeaderLine('Location'), '/login'), 'Cuenta temporal autenticada');
    return $client;
}

function personalRows(string $html): array
{
    $document = new DOMDocument;
    $previous = libxml_use_internal_errors(true);
    $document->loadHTML('<?xml encoding="UTF-8">'.$html);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    $rows = [];
    foreach ((new DOMXPath($document))->query('//tbody/tr') as $row) {
        $values = [];
        foreach ($row->getElementsByTagName('td') as $cell) { $values[] = trim($cell->textContent); }
        // Las primeras cinco columnas son los datos del listado; la sexta contiene acciones.
        $rows[] = array_slice($values, 0, 5);
    }
    return $rows;
}

$users = $spaces = [];
$suffix = bin2hex(random_bytes(6));
$path = '/residente/reservas';
try {
    foreach (['principal', 'otro', 'sin_reservas', 'Administrador', 'Conserje'] as $type) {
        $users[$type] = User::create(['nombre' => "HU24 $type", 'email' => "hu24-$type-$suffix@example.test", 'password' => 'PruebaHU24!123',
            'rol_id' => Rol::where('nombre', in_array($type, ['Administrador', 'Conserje']) ? $type : 'Residente')->firstOrFail()->id, 'estado' => 'activo']);
    }
    foreach (['activo', 'inactivo'] as $state) {
        $spaces[$state] = EspacioComun::create(['nombre' => "HU24 $suffix $state <script>texto</script>", 'descripcion' => 'Historial de pruebas', 'estado' => $state]);
    }
    $expected = [];
    // Ambos residentes reservan los mismos espacios: la privacidad depende del usuario, no del espacio.
    foreach (['principal', 'otro'] as $type) {
        for ($i = 0; $i < 13; $i++) {
            $space = $spaces[$i % 2 === 0 ? 'activo' : 'inactivo'];
            $state = array_keys(Reserva::ESTADOS)[$i % count(Reserva::ESTADOS)];
            $reservation = Reserva::create(['espacio_comun_id' => $space->id, 'user_id' => $users[$type]->id,
                'fecha' => now()->addDays($i - 6)->toDateString(), 'hora_inicio' => '10:15', 'hora_fin' => '11:15', 'estado' => $state]);
            $expected[$type][] = [$space->nombre, $reservation->fecha->format('d/m/Y'), '10:15–11:15',
                Reserva::ESTADOS[$state], '#'.$reservation->id];
        }
        $expected[$type] = array_reverse($expected[$type]);
    }
    foreach (['principal', 'otro'] as $type) {
        $client = personalLogin($users[$type]);
        $otherId = $users[$type === 'principal' ? 'otro' : 'principal']->id;
        $combined = [];
        foreach ([1, 2] as $page) {
            $response = $client->get($path, ['query' => ['page' => $page, 'user_id' => $otherId, 'residente_id' => $otherId]]);
            personalCheck($response->getStatusCode() === 200 && str_contains($response->getHeaderLine('Cache-Control'), 'no-store'), "$type: listado privado sin caché, página $page");
            $html = (string) $response->getBody();
            $rows = personalRows($html);
            personalCheck($rows === array_slice($expected[$type], ($page - 1) * 10, 10), "$type: espacio, fecha, horario y estado exactos, solo propios y en orden, página $page");
            personalCheck(str_contains($html, "Página $page de 2") && str_contains($html, $page === 1 ? 'Siguiente' : 'Anterior'), "$type: paginación calcula únicamente reservas propias");
            personalCheck(str_contains($html, '&lt;script&gt;texto&lt;/script&gt;') && !str_contains($html, '<script>texto</script>'), "$type: nombre del espacio se muestra como texto seguro");
            $combined = array_merge($combined, $rows);
        }
        personalCheck($combined === $expected[$type], "$type: historial completo incluye pasadas, futuras, todos los estados y espacios desactivados");
        $response = $client->get($path, ['query' => ['user_id' => [$otherId], 'page' => 1]]);
        personalCheck(personalRows((string) $response->getBody()) === array_slice($expected[$type], 0, 10), "$type: identificadores manipulados no cambian la cuenta consultada");
        $html = (string) $client->get($path, ['query' => ['page' => 99]])->getBody();
        personalCheck(personalRows($html) === [] && str_contains($html, 'Volver a la primera página')
            && !str_contains($html, 'Aún no tienes reservas'), "$type: página fuera de rango permite regresar sin confundir con historial vacío");
        personalCheck(str_contains((string) $client->get('/residente/dashboard')->getBody(), $path), "$type: acceso desde el portal");
    }
    $fresh = personalLogin($users['principal']);
    personalCheck(personalRows((string) $fresh->get($path)->getBody()) === array_slice($expected['principal'], 0, 10), 'Nueva sesión recupera reservas persistidas');
    $empty = personalLogin($users['sin_reservas']);
    $html = (string) $empty->get($path, ['query' => ['user_id' => $users['principal']->id]])->getBody();
    personalCheck(str_contains($html, 'Aún no tienes reservas registradas') && personalRows($html) === []
        && !str_contains($html, $suffix), 'Cuenta sin reservas muestra aviso sin datos de otras cuentas');
    foreach (['Administrador', 'Conserje'] as $role) {
        personalCheck(personalLogin($users[$role])->get($path)->getStatusCode() === 403, "$role no accede al listado personal de residentes");
    }
    $users['principal']->update(['estado' => 'inactivo']);
    personalCheck($fresh->get($path)->getStatusCode() === 302, 'Residente desactivado pierde acceso al listado');
    personalCheck(personalBrowser()->get($path)->getStatusCode() === 302, 'Invitado debe iniciar sesión');
    echo "HU-24: consulta personal de reservas verificada.\n";
} finally {
    Reserva::whereIn('espacio_comun_id', array_map(fn ($space) => $space->id, $spaces))->delete();
    foreach ($spaces as $space) { $space->delete(); }
    foreach ($users as $user) { $user->delete(); }
}
