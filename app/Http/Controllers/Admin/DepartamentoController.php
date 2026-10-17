<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactoEmergencia;
use App\Models\Departamento;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartamentoController extends Controller
{
    /**
     * Muestra el listado de departamentos con sus contadores y estadísticas (HU-07, HU-08).
     */
    public function index(Request $request): View
    {
        $filtroEstado = $request->query('estado');
        $busqueda = $request->query('buscar');

        $query = Departamento::query()
            ->withCount(['residentes', 'contactosEmergencia']);

        if ($filtroEstado && in_array($filtroEstado, ['activo', 'inactivo'], true)) {
            $query->where('estado', $filtroEstado);
        }

        if ($busqueda) {
            $query->where(function ($q) use ($busqueda) {
                $q->where('numero', 'like', "%{$busqueda}%")
                  ->orWhere('piso', 'like', "%{$busqueda}%");
            });
        }

        $departamentos = $query->orderBy('piso')
            ->orderBy('numero')
            ->paginate(12)
            ->withQueryString();

        $estadisticas = [
            'total' => Departamento::count(),
            'activos' => Departamento::where('estado', 'activo')->count(),
            'inactivos' => Departamento::where('estado', 'inactivo')->count(),
        ];

        return view('admin.departamentos.index', compact('departamentos', 'estadisticas', 'filtroEstado', 'busqueda'));
    }

    /**
     * Registra un nuevo departamento en el sistema (HU-07).
     */
    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'numero' => [
                'bail',
                'required',
                'string',
                'max:50',
                Rule::unique('departamentos', 'numero'),
            ],
            'piso' => ['bail', 'required', 'integer', 'min:-10', 'max:200'],
            'estado' => ['bail', 'nullable', 'string', Rule::in(['activo', 'inactivo'])],
        ], [
            'numero.required' => 'Ingresa el número o código de la unidad.',
            'numero.max' => 'El número no puede superar los 50 caracteres.',
            'numero.unique' => 'Ya existe un departamento registrado con este número.',
            'piso.required' => 'Ingresa el piso en el que se ubica.',
            'piso.integer' => 'El piso debe ser un número entero.',
            'piso.min' => 'El piso mínimo permitido es -10.',
            'piso.max' => 'El piso máximo permitido es 200.',
            'estado.in' => 'El estado debe ser activo o inactivo.',
        ]);

        $departamento = Departamento::create([
            'numero' => trim($datos['numero']),
            'piso' => $datos['piso'],
            'estado' => $datos['estado'] ?? 'activo',
        ]);

        return redirect()->route('admin.departamentos.index')
            ->with('estado', "El departamento {$departamento->numero} fue registrado correctamente.");
    }

    /**
     * Ficha técnica del departamento: residentes vinculados y contactos de emergencia (HU-09, HU-10).
     */
    public function show(Departamento $departamento): View
    {
        $departamento->load([
            'residentes' => function ($q) {
                $q->orderByPivot('es_titular', 'desc')->orderBy('nombre');
            },
            'contactosEmergencia.residente',
        ]);

        // Cargar residentes activos no asociados a este departamento
        $asociadosIds = $departamento->residentes->pluck('id')->toArray();

        $residentesDisponibles = User::whereHas('rol', function ($q) {
                $q->where('nombre', 'Residente');
            })
            ->where('estado', 'activo')
            ->whereNotIn('id', $asociadosIds)
            ->orderBy('nombre')
            ->get();

        return view('admin.departamentos.show', compact('departamento', 'residentesDisponibles'));
    }

    /**
     * Actualiza el estado activo / inactivo de un departamento (HU-08).
     */
    public function actualizarEstado(Request $request, Departamento $departamento): RedirectResponse
    {
        $datos = $request->validate([
            'estado' => ['bail', 'required', 'string', Rule::in(['activo', 'inactivo'])],
        ], [
            'estado.required' => 'Selecciona un estado válido.',
            'estado.in' => 'El estado debe ser activo o inactivo.',
        ]);

        $departamento->update([
            'estado' => $datos['estado'],
        ]);

        $accion = $datos['estado'] === 'activo' ? 'activado' : 'desactivado';

        return back()->with('estado', "El departamento {$departamento->numero} fue {$accion} correctamente.");
    }

    /**
     * Asocia una cuenta de residente al departamento activo (HU-09).
     * Valida estrictamente que el departamento se encuentre ACTIVO.
     */
    public function asociarResidente(Request $request, Departamento $departamento): RedirectResponse
    {
        // Validación estricta de estado del departamento (HU-08 / HU-09)
        if (! $departamento->estaActivo()) {
            return back()->withErrors([
                'residente_id' => 'No es posible asociar residentes a un departamento inactivo. Activa el departamento primero.',
            ]);
        }

        $datos = $request->validate([
            'residente_id' => ['bail', 'required', 'integer', 'exists:users,id'],
            'es_titular' => ['bail', 'nullable', 'boolean'],
        ], [
            'residente_id.required' => 'Selecciona un residente para asociar.',
            'residente_id.exists' => 'El residente seleccionado no existe.',
        ]);

        $residente = User::with('rol')->findOrFail($datos['residente_id']);

        if (! $residente->esResidente()) {
            return back()->withErrors([
                'residente_id' => 'El usuario seleccionado no cuenta con el rol de Residente.',
            ]);
        }

        if (! $residente->estaActivo()) {
            return back()->withErrors([
                'residente_id' => 'No puedes asociar un residente cuya cuenta esté inactiva.',
            ]);
        }

        if ($departamento->residentes()->where('user_id', $residente->id)->exists()) {
            return back()->withErrors([
                'residente_id' => 'El residente ya está asociado a este departamento.',
            ]);
        }

        $esTitular = $request->boolean('es_titular');

        // Si se define como titular principal, actualizamos los otros residentes del depto
        if ($esTitular) {
            $departamento->residentes()->updateExistingPivot(
                $departamento->residentes->pluck('id')->toArray(),
                ['es_titular' => false]
            );
        }

        $departamento->residentes()->attach($residente->id, [
            'es_titular' => $esTitular,
        ]);

        return back()->with('estado', "El residente {$residente->nombre} fue asociado exitosamente al departamento {$departamento->numero}.");
    }

    /**
     * Desasocia una cuenta de residente del departamento (HU-09).
     */
    public function desasociarResidente(Departamento $departamento, User $usuario): RedirectResponse
    {
        $departamento->residentes()->detach($usuario->id);

        return back()->with('estado', "El residente {$usuario->nombre} fue desasociado del departamento {$departamento->numero}.");
    }

    /**
     * Registra un nuevo contacto de emergencia vinculado al departamento o residente (HU-10).
     */
    public function storeContacto(Request $request, Departamento $departamento): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['bail', 'required', 'string', 'max:150'],
            'telefono' => [
                'bail',
                'required',
                'string',
                'max:30',
                'regex:/^(\+?)[0-9\s\-\(\)]{7,20}$/',
            ],
            'relacion' => ['bail', 'nullable', 'string', 'max:100'],
            'user_id' => ['bail', 'nullable', 'integer', 'exists:users,id'],
        ], [
            'nombre.required' => 'Ingresa el nombre del contacto de emergencia.',
            'nombre.max' => 'El nombre no puede superar los 150 caracteres.',
            'telefono.required' => 'Ingresa el teléfono del contacto de emergencia.',
            'telefono.regex' => 'Ingresa un formato de teléfono válido (ej: +56912345678 o 912345678).',
            'telefono.max' => 'El teléfono no puede superar los 30 caracteres.',
            'relacion.max' => 'La relación o parentesco no puede superar los 100 caracteres.',
            'user_id.exists' => 'El residente seleccionado no es válido.',
        ]);

        $departamento->contactosEmergencia()->create([
            'nombre' => trim($datos['nombre']),
            'telefono' => trim($datos['telefono']),
            'relacion' => ! empty($datos['relacion']) ? trim($datos['relacion']) : null,
            'user_id' => $datos['user_id'] ?? null,
        ]);

        return back()->with('estado', 'Contacto de emergencia registrado exitosamente.');
    }

    /**
     * Elimina un contacto de emergencia (HU-10).
     */
    public function destroyContacto(Departamento $departamento, ContactoEmergencia $contacto): RedirectResponse
    {
        if ($contacto->departamento_id !== $departamento->id) {
            abort(404);
        }

        $contacto->delete();

        return back()->with('estado', 'Contacto de emergencia eliminado correctamente.');
    }
}
