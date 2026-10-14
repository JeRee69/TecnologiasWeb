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
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:6',
        ], [
            'password.required' => 'La nueva contraseña es obligatoria.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'password.min' => 'La contraseña debe contener al menos 6 caracteres.',
        ]);

        $registro = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (!$registro) {
            return back()->withErrors(['email' => 'La solicitud de recuperación no existe.']);
        }

        $creadoEl = Carbon::parse($registro->created_at);
        if ($creadoEl->addMinutes(self::MINUTOS_VIGENCIA)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return redirect()->route('password.request')
                ->withErrors(['email' => 'El enlace de recuperación ha expirado. Solicita uno nuevo.']);
        }

        if (!Hash::check($request->token, $registro->token)) {
            return back()->withErrors(['email' => 'El token de recuperación no es válido.']);
        }

        $usuario = User::where('email', $request->email)->first();
        if (!$usuario) {
            return back()->withErrors(['email' => 'No se encontró el usuario.']);
        }

        $usuario->password = Hash::make($request->password);
        $usuario->save();

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

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
