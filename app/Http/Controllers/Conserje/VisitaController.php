<?php

namespace App\Http\Controllers\Conserje;

use App\Http\Controllers\Controller;
use App\Models\Departamento;
use App\Models\Visita;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VisitaController extends Controller
{
    public function index(Request $request): View
    {
        $filtro = $request->query('filtro', 'todas');
        $busqueda = $request->query('busqueda');

        $query = Visita::with(['departamento', 'conserje']);

        if ($filtro === 'en_recinto') {
            $query->enRecinto();
        } elseif ($filtro === 'finalizadas') {
            $query->finalizadas();
        }

        if (! empty($busqueda)) {
            $busquedaTrim = trim($busqueda);
            $query->where(function ($q) use ($busquedaTrim) {
                $q->where('nombre_visitante', 'like', "%{$busquedaTrim}%")
                    ->orWhere('rut_visitante', 'like', "%{$busquedaTrim}%")
                    ->orWhereHas('departamento', function ($sub) use ($busquedaTrim) {
                        $sub->where('numero', 'like', "%{$busquedaTrim}%");
                    });
            });
        }

        $visitas = $query->orderByRaw("CASE WHEN estado = 'En Recinto' THEN 0 ELSE 1 END")
            ->orderBy('fecha_ingreso', 'desc')
            ->paginate(15)
            ->withQueryString();

        $departamentos = Departamento::activos()
            ->orderBy('numero')
            ->get();

        $totalEnRecinto = Visita::enRecinto()->count();
        $totalHoy = Visita::whereDate('fecha_ingreso', today())->count();
        $totalHistorico = Visita::count();

        return view('conserje.visitas.index', [
            'visitas' => $visitas,
            'departamentos' => $departamentos,
            'filtro' => $filtro,
            'busqueda' => $busqueda,
            'metricas' => [
                'total_en_recinto' => $totalEnRecinto,
                'total_hoy' => $totalHoy,
                'total_historico' => $totalHistorico,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'departamento_id' => ['required', 'exists:departamentos,id'],
            'nombre_visitante' => ['required', 'string', 'max:150'],
            'rut_visitante' => ['nullable', 'string', 'max:20'],
            'fecha' => ['nullable', 'date'],
            'hora' => ['nullable', 'date_format:H:i'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ], [
            'departamento_id.required' => 'Debes seleccionar el departamento de destino.',
            'departamento_id.exists' => 'El departamento seleccionado no es válido.',
            'nombre_visitante.required' => 'El nombre del visitante es obligatorio.',
            'nombre_visitante.max' => 'El nombre no puede superar los 150 caracteres.',
            'rut_visitante.max' => 'El RUT no puede superar los 20 caracteres.',
            'fecha.date' => 'La fecha de ingreso no tiene un formato válido.',
            'hora.date_format' => 'La hora debe tener formato HH:MM.',
            'observaciones.max' => 'Las observaciones no pueden superar los 500 caracteres.',
        ]);

        $departamento = Departamento::findOrFail($datos['departamento_id']);

        if (! $departamento->estaActivo()) {
            return back()
                ->withInput()
                ->withErrors(['departamento_id' => 'No es posible registrar visitas para un departamento inactivo.']);
        }

        $fecha = $datos['fecha'] ?? null;
        $hora = $datos['hora'] ?? null;

        if ($fecha && $hora) {
            $fechaIngreso = Carbon::createFromFormat('Y-m-d H:i', "{$fecha} {$hora}");
        } elseif ($fecha) {
            $fechaIngreso = Carbon::parse($fecha)->setTimeFrom(now());
        } else {
            $fechaIngreso = now();
        }

        $visita = Visita::create([
            'departamento_id' => $departamento->id,
            'conserje_id' => $request->user()?->id,
            'nombre_visitante' => trim($datos['nombre_visitante']),
            'rut_visitante' => ! empty($datos['rut_visitante']) ? trim($datos['rut_visitante']) : null,
            'fecha_ingreso' => $fechaIngreso,
            'estado' => 'En Recinto',
            'observaciones' => ! empty($datos['observaciones']) ? trim($datos['observaciones']) : null,
        ]);

        return redirect()->route('conserje.visitas.index')
            ->with('estado', "Visita de {$visita->nombre_visitante} al departamento {$departamento->numero} registrada con éxito.");
    }

    public function registrarSalida(Request $request, Visita $visita): RedirectResponse
    {
        if ($visita->estado === 'Finalizada') {
            return back()->withErrors(['visita' => 'La salida de esta visita ya fue registrada anteriormente.']);
        }

        $visita->update([
            'estado' => 'Finalizada',
            'fecha_salida' => now(),
        ]);

        $depto = $visita->departamento?->numero ?? 'N/A';
        return redirect()->route('conserje.visitas.index')
            ->with('estado', "Salida de la visita {$visita->nombre_visitante} (Depto. {$depto}) registrada exitosamente.");
    }
}
