<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EspacioComun;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EspacioComunController extends Controller
{
    public function index(): View
    {
        return view('admin.espacios-comunes.index', [
            'espacios' => EspacioComun::orderByDesc('id')->paginate(10),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['bail', 'required', 'string', 'max:150', Rule::unique('espacios_comunes', 'nombre')],
            'descripcion' => ['bail', 'required', 'string', 'max:2000'],
            'estado' => ['bail', 'required', 'string', Rule::in(['activo', 'inactivo'])],
        ], [
            'nombre.required' => 'Ingresa el nombre del espacio.',
            'nombre.string' => 'Ingresa un nombre válido.',
            'nombre.max' => 'El nombre no puede superar los 150 caracteres.',
            'nombre.unique' => 'Ya existe un espacio común con este nombre.',
            'descripcion.required' => 'Describe el espacio común.',
            'descripcion.string' => 'Ingresa una descripción válida.',
            'descripcion.max' => 'La descripción no puede superar los 2000 caracteres.',
            'estado.required' => 'Selecciona el estado del espacio.',
            'estado.string' => 'Selecciona un estado válido.',
            'estado.in' => 'El estado debe ser activo o inactivo.',
        ]);

        EspacioComun::create($datos);

        return redirect()->route('admin.espacios-comunes.index')
            ->with('estado', 'El espacio común fue registrado correctamente.');
    }
}
