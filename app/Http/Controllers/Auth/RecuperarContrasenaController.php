<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use App\Rules\LimiteContrasenaBcrypt;
use Illuminate\Validation\ValidationException;

class RecuperarContrasenaController extends Controller
{
    private const MINUTOS_VIGENCIA = 60;

    public function mostrarFormularioSolicitud(): View
    {
        return view('auth.recuperar-contrasena');
    }

    public function enviarTokenRecuperacion(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un formato de correo electrónico válido.',
        ]);

        $usuario = User::where('email', $request->email)->first();

        if (!$usuario) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'No encontramos ninguna cuenta asociada a este correo electrónico.']);
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => Hash::make($token),
                'created_at' => Carbon::now(),
            ]
        );

        $enlaceRecuperacion = route('password.reset', ['token' => $token, 'email' => $request->email]);

        // En desarrollo local dejamos el enlace disponible en la sesión para pruebas inmediatas
        return back()->with([
            'estado' => 'Hemos generado tu enlace de recuperación con vigencia de ' . self::MINUTOS_VIGENCIA . ' minutos.',
            'enlace_simulado' => $enlaceRecuperacion
        ]);
    }

    public function mostrarFormularioRestablecer(Request $request, string $token): View|RedirectResponse
    {
        $email = $request->query('email');

        if (!$email) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'El enlace de recuperación es inválido.']);
        }

        $registro = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$registro) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'No existe una solicitud de recuperación para este correo.']);
        }

        $creadoEl = Carbon::parse($registro->created_at);
        if ($creadoEl->addMinutes(self::MINUTOS_VIGENCIA)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return redirect()->route('password.request')
                ->withErrors(['email' => 'El enlace de recuperación ha expirado. Por favor, solicita uno nuevo.']);
        }

        return view('auth.restablecer-contrasena', [
            'token' => $token,
            'email' => $email
        ]);
    }

    public function restablecerContrasena(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => ['bail', 'required', 'string', 'confirmed', 'min:12', new LimiteContrasenaBcrypt],
        ], [
            'password.required' => 'La nueva contraseña es obligatoria.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'password.min' => 'La contraseña debe contener al menos 12 caracteres.',
        ]);
        DB::transaction(function () use ($request) {
            $usuario = User::where('email', $request->email)->lockForUpdate()->first();
            $registro = DB::table('password_reset_tokens')->where('email', $request->email)->lockForUpdate()->first();
            if (!$usuario || !$registro || Carbon::parse($registro->created_at)->addMinutes(self::MINUTOS_VIGENCIA)->isPast()
                || !Hash::check($request->token, $registro->token)) {
                throw ValidationException::withMessages(['email' => 'El enlace de recuperación es inválido o ha expirado. Solicita uno nuevo.']);
            }
            $usuario->forceFill(['password' => Hash::make($request->password), 'remember_token' => Str::random(60)])->save();
            $usuario->tokens()->delete();
            DB::table('password_reset_tokens')->where('email', $usuario->email)->delete();
        }, 3);

        return redirect()->route('login')->with('estado', 'Tu contraseña ha sido restablecida con éxito. Puedes iniciar sesión ahora.');
    }

    public function apiForgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $usuario = User::where('email', $request->email)->first();

        if (!$usuario) {
            return response()->json([
                'message' => 'Correo no registrado'
            ], 404);
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => Hash::make($token),
                'created_at' => Carbon::now(),
            ]
        );

        return response()->json([
            'message' => 'Correo enviado'
        ], 200);
    }
}
