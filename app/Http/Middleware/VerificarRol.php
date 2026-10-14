<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerificarRol
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['mensaje' => 'No autenticado'], 401);
            }
            return redirect()->route('login')->withErrors(['identifier' => 'Debes iniciar sesión para continuar.']);
        }

        $usuario = Auth::user();

        if (!$usuario->estaActivo()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['mensaje' => 'Tu cuenta se encuentra inactiva. Contacta al administrador.'], 403);
            }
            return redirect()->route('login')->withErrors(['identifier' => 'Tu cuenta se encuentra inactiva. Contacta al administrador.']);
        }

        if (empty($roles)) {
            return $next($request);
        }

        $rolUsuario = strtolower($usuario->rol?->nombre ?? '');
        $rolesPermitidos = array_map('strtolower', $roles);

        if (!in_array($rolUsuario, $rolesPermitidos)) {
            if ($request->expectsJson()) {
                return response()->json(['mensaje' => 'Acceso denegado: rol no autorizado'], 403);
            }
            return response()->view('errores.403', [
                'mensaje' => 'No tienes los permisos requeridos para acceder a esta sección.',
                'rolUsuario' => $usuario->rol?->nombre ?? 'Sin rol'
            ], 403);
        }

        return $next($request);
    }
}
