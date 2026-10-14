<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function index(): View
    {
        return view('admin.usuarios.index', [
            'usuarios' => User::with('rol')->orderBy('nombre')->orderBy('id')->paginate(15),
        ]);
    }

    public function actualizarEstado(Request $request, User $usuario): RedirectResponse
    {
        $datos = $request->validate([
            'estado' => ['required', Rule::in(['activo', 'inactivo'])],
        ], [
            'estado.required' => 'Selecciona el estado de la cuenta.',
            'estado.in' => 'El estado debe ser activo o inactivo.',
        ]);

        if ($request->user()->is($usuario) && $datos['estado'] === 'inactivo') {
            return back()->withErrors(['estado' => 'No puedes desactivar tu propia cuenta.']);
        }

        DB::transaction(function () use ($usuario, $datos) {
            $cuenta = User::whereKey($usuario->id)->lockForUpdate()->firstOrFail();
            $cuenta->estado = $datos['estado'];

            if ($datos['estado'] === 'inactivo') {
                $cuenta->remember_token = Str::random(60);
                $cuenta->tokens()->delete();
            }

            $cuenta->save();
        });

        $accion = $datos['estado'] === 'activo' ? 'activada' : 'desactivada';
        return back()->with('estado', "La cuenta de {$usuario->nombre} fue {$accion} correctamente.");
    }
}
