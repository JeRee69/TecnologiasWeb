<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, string ...$guards)
    {
        foreach ($guards ?: [null] as $guard) {
            if (Auth::guard($guard)->check()) {
                $route = match (strtolower(Auth::guard($guard)->user()->rol?->nombre ?? '')) {
                    'administrador' => 'admin.dashboard',
                    'conserje' => 'conserje.dashboard',
                    'residente' => 'residente.dashboard',
                    default => null,
                };
                abort_unless($route, 403);
                return redirect()->route($route);
            }
        }
        return $next($request);
    }
}
