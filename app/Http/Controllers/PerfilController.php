<?php

namespace App\Http\Controllers;

use Closure;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PerfilController extends Controller
{
    public function editar(Request $request): View
    {
        $usuario = $request->user()->load('rol');
        $rutaPanel = match (strtolower($usuario->rol?->nombre ?? '')) {
            'administrador' => 'admin.dashboard',
            'conserje' => 'conserje.dashboard',
            'residente' => 'residente.dashboard',
            default => 'login',
        };

        return view('perfil.editar', compact('usuario', 'rutaPanel'));
    }

    public function actualizar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'telefono' => [
                'bail', 'sometimes', 'nullable', 'string', 'max:30', 'regex:/^\+?[0-9 ()-]+$/',
                function (string $attribute, mixed $value, Closure $fail) {
                    $digitos = strlen(preg_replace('/\D/', '', $value));
                    if ($digitos < 7 || $digitos > 15) {
                        $fail('El teléfono debe contener entre 7 y 15 dígitos.');
                    }
                },
            ],
            'email_secundario' => ['bail', 'sometimes', 'nullable', 'string', 'email:rfc', 'max:254'],
        ], [
            'telefono.string' => 'Ingresa un teléfono válido.',
            'telefono.max' => 'El teléfono no puede superar los 30 caracteres.',
            'telefono.regex' => 'Usa números, espacios, paréntesis o guiones y un + inicial opcional.',
            'email_secundario.string' => 'Ingresa un correo secundario válido.',
            'email_secundario.email' => 'Ingresa un correo secundario válido.',
            'email_secundario.max' => 'El correo secundario no puede superar los 254 caracteres.',
        ]);

        // La cuenta se obtiene de la sesión; solo se guardan los campos habilitados.
        $request->user()->update($datos);

        return redirect()->route('perfil.editar')->with('estado', 'Tus datos de contacto se actualizaron correctamente.');
    }

    public function actualizarContrasena(Request $request): RedirectResponse
    {
        $datos = $request->validateWithBag('contrasena', [
            'current_password' => ['bail', 'required', 'string', 'current_password:web'],
            'password' => [
                'bail', 'required', 'string', 'min:12', 'confirmed', 'different:current_password',
                function (string $attribute, mixed $value, Closure $fail) {
                    // Bcrypt admite hasta 72 bytes, también para caracteres multibyte.
                    if (strlen($value) > 72 || str_contains($value, "\0")) {
                        $fail('La contraseña debe ocupar como máximo 72 bytes y no contener caracteres nulos.');
                    }
                },
            ],
        ], [
            'current_password.required' => 'Ingresa tu contraseña actual.',
            'current_password.string' => 'Ingresa tu contraseña actual.',
            'current_password.current_password' => 'La contraseña actual es incorrecta.',
            'password.required' => 'Ingresa la nueva contraseña.',
            'password.string' => 'Ingresa una contraseña válida.',
            'password.min' => 'La nueva contraseña debe contener al menos 12 caracteres.',
            'password.confirmed' => 'La confirmación de la nueva contraseña no coincide.',
            'password.different' => 'La nueva contraseña debe ser diferente de la actual.',
        ]);

        $usuario = DB::transaction(function () use ($request, $datos) {
            $cuenta = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            if (! Hash::check($datos['current_password'], $cuenta->password)) {
                throw ValidationException::withMessages([
                    'current_password' => 'La contraseña actual es incorrecta.',
                ])->errorBag('contrasena');
            }
            $cuenta->forceFill([
                'password' => Hash::make($datos['password']),
                'remember_token' => Str::random(60),
            ])->save();
            $cuenta->tokens()->delete();
            DB::table('password_reset_tokens')->where('email', $cuenta->email)->delete();
            return $cuenta;
        });

        Auth::setUser($usuario);
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect()->route('perfil.editar')->with('estado', 'Tu contraseña fue actualizada. Las demás sesiones y los accesos recordados deberán iniciar sesión nuevamente.');
    }
}
