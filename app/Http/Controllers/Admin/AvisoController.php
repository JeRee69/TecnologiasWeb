<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Aviso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AvisoController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'estado' => ['nullable', 'string', Rule::in(array_keys(Aviso::ESTADOS))],
            'prioridad' => ['nullable', 'string', Rule::in(array_keys(Aviso::PRIORIDADES))],
            'busqueda' => ['nullable', 'string', 'max:150'],
        ], ['busqueda.string' => 'La búsqueda debe ser texto.']);
        $filtroEstado = $filtros['estado'] ?? null;
        $filtroPrioridad = $filtros['prioridad'] ?? null;
        $busqueda = trim($filtros['busqueda'] ?? '');

        $query = Aviso::with('autor');

        if (!empty($filtroEstado) && in_array($filtroEstado, Aviso::ESTADOS, true)) {
            $query->where('estado', $filtroEstado);
        }

        if (!empty($filtroPrioridad) && in_array($filtroPrioridad, Aviso::PRIORIDADES, true)) {
            $query->where('prioridad', $filtroPrioridad);
        }

        if (!empty($busqueda)) {
            $query->where(function ($q) use ($busqueda) {
                $q->where('titulo', 'like', "%{$busqueda}%")
                  ->orWhere('contenido', 'like', "%{$busqueda}%");
            });
        }

        $avisos = $query->prioridadOrdenada()->paginate(10)->withQueryString();

        $totales = [
            'total' => Aviso::count(),
            'publicados' => Aviso::where('estado', 'Publicado')->count(),
            'retirados' => Aviso::where('estado', 'Retirado')->count(),
            'urgentes' => Aviso::where('estado', 'Publicado')->where('prioridad', 'Urgente')->count(),
        ];

        return view('admin.avisos.index', compact('avisos', 'totales', 'filtroEstado', 'filtroPrioridad', 'busqueda'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'titulo' => ['required', 'string', 'max:150'],
            'contenido' => ['required', 'string', 'max:5000'],
            'prioridad' => ['required', 'string', Rule::in(array_keys(Aviso::PRIORIDADES))],
            'estado' => ['nullable', 'string', Rule::in(array_keys(Aviso::ESTADOS))],
        ], [
            'titulo.required' => 'El título del aviso es obligatorio.',
            'titulo.max' => 'El título no puede superar los 150 caracteres.',
            'contenido.required' => 'El contenido del comunicado es obligatorio.',
            'prioridad.required' => 'Selecciona un nivel de prioridad válido.',
            'prioridad.in' => 'La prioridad seleccionada no es válida.',
        ]);

        $aviso = Aviso::create([
            'autor_id' => $request->user()->id,
            'titulo' => $validated['titulo'],
            'contenido' => $validated['contenido'],
            'prioridad' => $validated['prioridad'],
            'estado' => $validated['estado'] ?? 'Publicado',
            'fecha_publicacion' => now(),
        ]);

        return redirect()->route('admin.avisos.index')->with('exito', "Aviso '{$aviso->titulo}' publicado exitosamente.");
    }

    public function edit(Aviso $aviso): View
    {
        return view('admin.avisos.edit', compact('aviso'));
    }

    public function update(Request $request, Aviso $aviso): RedirectResponse
    {
        $validated = $request->validate([
            'titulo' => ['required', 'string', 'max:150'],
            'contenido' => ['required', 'string', 'max:5000'],
            'prioridad' => ['required', 'string', Rule::in(array_keys(Aviso::PRIORIDADES))],
            'estado' => ['required', 'string', Rule::in(array_keys(Aviso::ESTADOS))],
        ], [
            'titulo.required' => 'El título del aviso es obligatorio.',
            'titulo.max' => 'El título no puede superar los 150 caracteres.',
            'contenido.required' => 'El contenido del comunicado es obligatorio.',
            'prioridad.required' => 'Selecciona un nivel de prioridad válido.',
            'estado.required' => 'Debes indicar el estado del aviso.',
        ]);

        $aviso->update($validated);

        return redirect()->route('admin.avisos.index')->with('exito', "Aviso '{$aviso->titulo}' actualizado correctamente.");
    }

    public function cambiarEstado(Request $request, Aviso $aviso): RedirectResponse
    {
        $nuevoEstado = $request->input('estado');

        if (!empty($nuevoEstado) && in_array($nuevoEstado, Aviso::ESTADOS, true)) {
            $aviso->estado = $nuevoEstado;
        } else {
            $aviso->estado = ($aviso->estado === 'Publicado') ? 'Retirado' : 'Publicado';
        }

        $aviso->save();

        $accion = $aviso->estado === 'Publicado' ? 'activado y publicado' : 'retirado de la vista pública';
        return redirect()->route('admin.avisos.index')->with('exito', "El aviso ha sido {$accion} exitosamente.");
    }
}
