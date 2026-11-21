<?php

namespace App\Services;

use App\Mail\RecuperarContrasena;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class RecuperacionContrasena
{
    public const MINUTOS_VIGENCIA = 60;

    public const MENSAJE_SOLICITUD = 'Si el correo está registrado, recibirás un enlace para restablecer tu contraseña con vigencia de 60 minutos.';

    public function solicitar(string $email): void
    {
        DB::transaction(function () use ($email) {
            // Compartir bloqueo con el restablecimiento y serializar solicitudes de la misma cuenta.
            $usuario = User::where('email', $email)->lockForUpdate()->first();
            if (!$usuario) {
                return;
            }

            $anterior = DB::table('password_reset_tokens')->where('email', $usuario->email)->first();
            if ($anterior && Carbon::parse($anterior->created_at)->addSeconds(60)->isFuture()) {
                return;
            }

            $token = Str::random(64);
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $usuario->email],
                ['token' => Hash::make($token), 'created_at' => now()]
            );

            // APP_URL define el destino; no usar el Host recibido del solicitante.
            $enlace = rtrim(config('app.url'), '/').route('password.reset', [
                'token' => $token, 'email' => $usuario->email,
            ], false);

            try {
                Mail::to($usuario->email)->send(new RecuperarContrasena(
                    $usuario->nombre, $enlace, self::MINUTOS_VIGENCIA
                ));
            } catch (Throwable $error) {
                // No registrar el token, la API key ni el cuerpo del correo.
                Log::warning('Falló el envío del correo de recuperación.', ['tipo' => $error::class]);
                throw ValidationException::withMessages([
                    'email' => 'No fue posible enviar el enlace de recuperación. Inténtalo nuevamente más tarde.',
                ]);
            }
        });
        // Si falla el envío, la transacción conserva el enlace anterior y revierte el nuevo.
    }
}
