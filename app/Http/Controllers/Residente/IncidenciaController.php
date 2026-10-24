<?php

namespace App\Http\Controllers\Residente;

use App\Http\Controllers\Controller;
use App\Models\Departamento;
use App\Models\Incidencia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class IncidenciaController extends Controller
{
    public function index(Request $request): View
    {
        $usuario = $request->user();
        $filtroEstado = $request->query('estado');
        $filtroCategoria = $request->query('categoria');

        $query = Incidencia::with(['departamento', 'personalResolucion'])
            ->deUsuario($usuario->id);

        if (!empty($filtroEstado) && in_array($filtroEstado, Incidencia::ESTADOS, true)) {
            $query->porEstado($filtroEstado);
        }

        if (!empty($filtroCategoria) && in_array($filtroCategoria, Incidencia::CATEGORIAS, true)) {
            $query->porCategoria($filtroCategoria);
        }

        $incidencias = $query->latest()->paginate(10)->withQueryString();

        $totales = [
            'total' => Incidencia::deUsuario($usuario->id)->count(),
            'pendientes' => Incidencia::deUsuario($usuario->id)->porEstado('Pendiente')->count(),
            'en_proceso' => Incidencia::deUsuario($usuario->id)->porEstado('En Proceso')->count(),
            'resueltas' => Incidencia::deUsuario($usuario->id)->porEstado('Resuelta')->count(),
            'rechazadas' => Incidencia::deUsuario($usuario->id)->porEstado('Rechazada')->count(),
        ];

        return view('residente.incidencias.index', compact('incidencias', 'totales', 'filtroEstado', 'filtroCategoria'));
    }

    public function create(Request $request): View
    {
        $usuario = $request->user();
        $departamentos = $usuario->departamentos()->activos()->orderBy('numero')->get();

        return view('residente.incidencias.create', [
            'departamentos' => $departamentos,
            'categorias' => Incidencia::CATEGORIAS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $usuario = $request->user();
        $departamentosUsuarioIds = $usuario->departamentos()->pluck('departamentos.id')->toArray();

        $validated = $request->validate([
            'departamento_id' => [
                'nullable',
                'integer',
                Rule::in($departamentosUsuarioIds),
            ],
            'categoria' => [
                'required',
                'string',
                Rule::in(Incidencia::CATEGORIAS),
            ],
            'titulo' => ['required', 'string', 'max:150'],
            'descripcion' => ['required', 'string', 'max:5000'],
        ], [
            'categoria.required' => 'Debes seleccionar una categoría de incidencia.',
            'categoria.in' => 'La categoría seleccionada no es válida.',
            'titulo.required' => 'Indica un título o asunto breve para la incidencia.',
            'titulo.max' => 'El título no puede superar los 150 caracteres.',
            'descripcion.required' => 'Describe detalladamente la situación o problema ocurrido.',
            'departamento_id.in' => 'El departamento seleccionado debe ser uno al que estés asignado.',
        ]);

        $departamentoId = $validated['departamento_id'] ?? null;
        if (!$departamentoId && !empty($departamentosUsuarioIds)) {
            $departamentoId = $departamentosUsuarioIds[0];
        }

        $incidencia = Incidencia::create([
            'user_id' => $usuario->id,
            'departamento_id' => $departamentoId,
            'categoria' => $validated['categoria'],
            'titulo' => $validated['titulo'],
            'descripcion' => $validated['descripcion'],
            'estado' => 'Pendiente',
        ]);

        return redirect()->route('residente.incidencias.index')
            ->with('exito', "Incidencia #{$incidencia->id} registrada exitosamente bajo estado 'Pendiente'.");
    }

    public function show(Request $request, Incidencia $incidencia): View
    {
        if ($incidencia->user_id !== $request->user()->id) {
            abort(403, 'No tienes autorización para consultar esta incidencia.');
        }

        $incidencia->load(['departamento', 'personalResolucion']);

        return view('residente.incidencias.show', compact('incidencia'));
    }
}
