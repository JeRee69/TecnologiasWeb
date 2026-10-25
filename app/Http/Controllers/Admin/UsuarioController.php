<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Rules\LimiteContrasenaBcrypt;

class UsuarioController extends Controller
{
    public function index(): View
    {
        return view('admin.usuarios.index', [
            'usuarios' => User::with('rol')->orderBy('nombre')->orderBy('id')->paginate(15),
            'roles' => Rol::orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'rut' => ['nullable', 'string', 'max:20', 'unique:users,rut'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'rol_id' => ['required', 'exists:roles,id'],
            'password' => ['bail', 'nullable', 'string', 'min:12', new LimiteContrasenaBcrypt],
        ], [
            'nombre.required' => 'El nombre del usuario es obligatorio.',
            'nombre.max' => 'El nombre no puede exceder los 150 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debes ingresar un correo electrónico válido.',
            'email.unique' => 'El correo electrónico ya se encuentra registrado.',
            'rut.unique' => 'El RUT ingresado ya se encuentra registrado.',
            'rol_id.required' => 'Debes seleccionar un rol para el usuario.',
            'rol_id.exists' => 'El rol seleccionado no es válido.',
            'password.min' => 'La contraseña debe contener al menos 12 caracteres.',
        ]);

        $passwordPlano = !empty($datos['password'])
            ? $datos['password']
            : Str::random(16);

        $usuario = User::create([
            'nombre' => trim($datos['nombre']),
            'email' => strtolower(trim($datos['email'])),
            'rut' => !empty($datos['rut']) ? trim($datos['rut']) : null,
            'telefono' => !empty($datos['telefono']) ? trim($datos['telefono']) : null,
            'password' => Hash::make($passwordPlano),
            'rol_id' => $datos['rol_id'],
            'estado' => 'activo',
        ]);

        $rolNombre = $usuario->rol?->nombre ?? 'Asignado';

        return redirect()->route('admin.usuarios.index')
            ->with('estado', "El usuario {$usuario->nombre} fue registrado exitosamente con el rol {$rolNombre}.")
            ->with('credencial_generada', [
                'email' => $usuario->email,
                'password' => $passwordPlano,
                'rol' => $rolNombre,
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
