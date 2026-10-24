<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Incidencia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class IncidenciaController extends Controller
{
    public function index(Request $request): View
    {
        $filtroEstado = $request->query('estado');
        $filtroCategoria = $request->query('categoria');
        $busqueda = trim($request->query('busqueda', ''));

        $query = Incidencia::with(['residente', 'departamento', 'personalResolucion']);

        if (!empty($filtroEstado) && in_array($filtroEstado, Incidencia::ESTADOS, true)) {
            $query->porEstado($filtroEstado);
        }

        if (!empty($filtroCategoria) && in_array($filtroCategoria, Incidencia::CATEGORIAS, true)) {
            $query->porCategoria($filtroCategoria);
        }

        if (!empty($busqueda)) {
            $query->where(function ($q) use ($busqueda) {
                $q->where('titulo', 'like', "%{$busqueda}%")
                  ->orWhere('descripcion', 'like', "%{$busqueda}%")
                  ->orWhereHas('residente', fn ($u) => $u->where('nombre', 'like', "%{$busqueda}%")->orWhere('email', 'like', "%{$busqueda}%"))
                  ->orWhereHas('departamento', fn ($d) => $d->where('numero', 'like', "%{$busqueda}%"));
            });
        }

        $incidencias = $query->latest()->paginate(10)->withQueryString();

        $totales = [
            'total' => Incidencia::count(),
            'pendientes' => Incidencia::porEstado('Pendiente')->count(),
            'en_proceso' => Incidencia::porEstado('En Proceso')->count(),
            'resueltas' => Incidencia::porEstado('Resuelta')->count(),
            'rechazadas' => Incidencia::porEstado('Rechazada')->count(),
        ];

        return view('admin.incidencias.index', compact('incidencias', 'totales', 'filtroEstado', 'filtroCategoria', 'busqueda'));
    }

    public function show(Incidencia $incidencia): View
    {
        $incidencia->load(['residente', 'departamento', 'personalResolucion']);
        $estados = Incidencia::ESTADOS;

        return view('admin.incidencias.show', compact('incidencia', 'estados'));
    }

    public function actualizarEstado(Request $request, Incidencia $incidencia): RedirectResponse
    {
        $validated = $request->validate([
            'estado' => ['required', 'string', Rule::in(array_keys(Incidencia::ESTADOS))],
            'respuesta_administracion' => [
                Rule::requiredIf(fn () => in_array($request->input('estado'), ['Resuelta', 'Rechazada'], true)),
                'nullable',
                'string',
                'max:5000',
            ],
        ], [
            'estado.required' => 'Debes indicar un nuevo estado.',
            'estado.in' => 'El estado seleccionado no es válido.',
            'respuesta_administracion.required_if' => 'Para resolver o rechazar una incidencia es obligatorio ingresar una respuesta explicativa.',
        ]);

        $incidencia->estado = $validated['estado'];
        if (isset($validated['respuesta_administracion'])) {
            $incidencia->respuesta_administracion = $validated['respuesta_administracion'];
        }
        $incidencia->personal_resolucion_id = $request->user()->id;

        if (in_array($validated['estado'], ['Resuelta', 'Rechazada'], true)) {
            $incidencia->fecha_resolucion = now();
        } else {
            $incidencia->fecha_resolucion = null;
        }

        $incidencia->save();

        return redirect()->route('admin.incidencias.show', $incidencia)
            ->with('exito', "Incidencia #{$incidencia->id} actualizada al estado '{$incidencia->estado}'.");
    }
}
