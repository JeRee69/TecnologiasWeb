<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AutenticacionController extends Controller
{
    public function mostrarLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirigirPorRol(Auth::user());
        }

        return view('auth.login');
    }

    public function iniciarSesion(Request $request): RedirectResponse
    {
        $request->validate([
            'identifier' => 'required|string',
            'password' => 'required|string',
        ], [
            'identifier.required' => 'El correo electrónico o RUT es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        $credencial = trim($request->input('identifier'));
        $password = $request->input('password');
        $recordar = $request->boolean('remember');

        $campo = filter_var($credencial, FILTER_VALIDATE_EMAIL) ? 'email' : 'rut';

        $usuario = User::with('rol')->where($campo, $credencial)->first();

        if (!$usuario || !Hash::check($password, $usuario->password)) {
            return back()
                ->withInput($request->only('identifier', 'remember'))
                ->withErrors(['identifier' => 'Credenciales inválidas']);
        }

        if (!$usuario->estaActivo()) {
            return back()
                ->withInput($request->only('identifier'))
                ->withErrors(['identifier' => 'La cuenta se encuentra inactiva. Contacta al administrador.']);
        }

        Auth::login($usuario, $recordar);
        $request->session()->regenerate();
        $request->session()->put('password_hash_web', Auth::guard('web')->hashPasswordForCookie($usuario->getAuthPassword()));

        return $this->redirigirPorRol($usuario);
    }

    public function cerrarSesion(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('estado', 'Has cerrado sesión exitosamente.');
    }

    public function apiLogin(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $usuario = User::with('rol')
            ->where('email', $request->email)
            ->orWhere('rut', $request->email)
            ->first();

        if (!$usuario || !Hash::check($request->password, $usuario->password)) {
            return response()->json([
                'mensaje' => 'Credenciales inválidas'
            ], 401);
        }

        if (!$usuario->estaActivo()) {
            return response()->json([
                'mensaje' => 'Cuenta inactiva'
            ], 403);
        }

        $token = $usuario->createToken('token_acceso')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'email' => $usuario->email,
                'rol' => $usuario->rol?->nombre ?? 'Sin rol'
            ]
        ], 200);
    }

    private function redirigirPorRol(User $usuario): RedirectResponse
    {
        $rol = strtolower($usuario->rol?->nombre ?? '');

        return match ($rol) {
            'administrador' => redirect()->route('admin.dashboard'),
            'conserje' => redirect()->route('conserje.dashboard'),
            'residente' => redirect()->route('residente.dashboard'),
            default => redirect()->route('login')->withErrors(['identifier' => 'Rol de usuario no reconocido']),
        };
    }
}
