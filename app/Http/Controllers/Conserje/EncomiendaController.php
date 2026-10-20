<?php

namespace App\Http\Controllers\Conserje;

use App\Http\Controllers\Controller;
use App\Models\Departamento;
use App\Models\Encomienda;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EncomiendaController extends Controller
{
    public function index(Request $request): View
    {
        $orden = $request->query('orden', 'recientes');
        $filtro = $request->query('filtro', 'todas');

        $query = Encomienda::with(['departamento', 'conserje']);

        // Filtro por estado o permanencia
        if ($filtro === 'pendientes') {
            $query->pendientes();
        } elseif ($filtro === 'prolongadas') {
            $query->pendientes()->where('fecha_ingreso', '<=', now()->subDays(3));
        }

        // Ordenamiento (HU-28: permite identificar aquellas con mayor tiempo de permanencia)
        if ($orden === 'mayor_permanencia') {
            $query->orderByRaw("CASE WHEN estado = 'Retirado' THEN 1 ELSE 0 END")
                ->orderBy('fecha_ingreso', 'asc')
                ->orderBy('id', 'asc');
        } else {
            $query->orderByRaw("CASE WHEN estado = 'Retirado' THEN 1 ELSE 0 END")
                ->orderBy('fecha_ingreso', 'desc')
                ->orderBy('id', 'desc');
        }

        $encomiendas = $query->paginate(15)->withQueryString();

        $departamentos = Departamento::activos()
            ->orderBy('numero')
            ->get();

        // Métricas de permanencia para conserjería (HU-28)
        $totalPendientes = Encomienda::pendientes()->count();
        $mayorPermanencia = Encomienda::pendientes()->orderBy('fecha_ingreso', 'asc')->first();
        $totalProlongadas = Encomienda::pendientes()->where('fecha_ingreso', '<=', now()->subDays(3))->count();

        return view('conserje.encomiendas.index', [
            'encomiendas' => $encomiendas,
            'departamentos' => $departamentos,
            'orden' => $orden,
            'filtro' => $filtro,
            'metricas' => [
                'total_pendientes' => $totalPendientes,
                'mayor_permanencia' => $mayorPermanencia,
                'total_prolongadas' => $totalProlongadas,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'departamento_id' => ['required', 'exists:departamentos,id'],
            'empresa_delivery' => ['required', 'string', 'max:100'],
            'fecha' => ['nullable', 'date'],
            'hora' => ['nullable', 'date_format:H:i'],
            'codigo_seguimiento' => ['nullable', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:500'],
        ], [
            'departamento_id.required' => 'Debes seleccionar el departamento destinatario.',
            'departamento_id.exists' => 'El departamento seleccionado no es válido.',
            'empresa_delivery.required' => 'La empresa de delivery o transportista es obligatoria.',
            'empresa_delivery.max' => 'El nombre de la empresa no puede superar los 100 caracteres.',
            'fecha.date' => 'La fecha ingresada no tiene un formato válido.',
            'hora.date_format' => 'La hora debe tener formato HH:MM.',
            'codigo_seguimiento.max' => 'El código de seguimiento no puede superar los 100 caracteres.',
            'descripcion.max' => 'La descripción no puede superar los 500 caracteres.',
        ]);

        $departamento = Departamento::findOrFail($datos['departamento_id']);

        if (! $departamento->estaActivo()) {
            return back()
                ->withInput()
                ->withErrors(['departamento_id' => 'No es posible registrar encomiendas para un departamento inactivo.']);
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

        $encomienda = Encomienda::create([
            'departamento_id' => $departamento->id,
            'empresa_delivery' => trim($datos['empresa_delivery']),
            'fecha_ingreso' => $fechaIngreso,
            'estado' => 'Ingresado',
            'codigo_seguimiento' => !empty($datos['codigo_seguimiento']) ? trim($datos['codigo_seguimiento']) : null,
            'descripcion' => !empty($datos['descripcion']) ? trim($datos['descripcion']) : null,
            'conserje_id' => $request->user()?->id,
        ]);

        return redirect()->route('conserje.encomiendas.index')
            ->with('estado', "Encomienda recibida correctamente para el departamento {$departamento->numero} ({$encomienda->empresa_delivery}).");
    }

    public function registrarRetiro(Request $request, Encomienda $encomienda): RedirectResponse
    {
        if ($encomienda->estado === 'Retirado') {
            return back()->withErrors(['encomienda' => 'Esta encomienda ya fue retirada anteriormente.']);
        }

        $encomienda->update([
            'estado' => 'Retirado',
            'fecha_retiro' => now(),
        ]);

        $deptoNumero = $encomienda->departamento?->numero ?? 'N/A';
        return redirect()->route('conserje.encomiendas.index')
            ->with('estado', "Retiro registrado exitosamente para el departamento {$deptoNumero} ({$encomienda->empresa_delivery}).");
    }
}
