<?php

namespace App\Http\Controllers;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
}
