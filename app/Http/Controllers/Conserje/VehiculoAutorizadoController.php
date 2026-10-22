<?php

namespace App\Http\Controllers\Conserje;

use App\Http\Controllers\Controller;
use App\Models\Departamento;
use App\Models\VehiculoAutorizado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VehiculoAutorizadoController extends Controller
{
    public function index(Request $request): View
    {
        $filtro = $request->query('filtro', 'todos');
        $busqueda = $request->query('busqueda');
        $verificarPatente = $request->query('verificar_patente');

        $query = VehiculoAutorizado::with(['departamento', 'conserje']);

        if ($filtro === 'autorizados') {
            $query->autorizados();
        } elseif ($filtro === 'revocados') {
            $query->revocados();
        }

        if (! empty($busqueda)) {
            $busquedaTrim = trim($busqueda);
            $query->where(function ($q) use ($busquedaTrim) {
                $q->where('patente', 'like', "%{$busquedaTrim}%")
                    ->orWhere('marca', 'like', "%{$busquedaTrim}%")
                    ->orWhere('modelo', 'like', "%{$busquedaTrim}%")
                    ->orWhereHas('departamento', function ($sub) use ($busquedaTrim) {
                        $sub->where('numero', 'like', "%{$busquedaTrim}%");
                    });
            });
        }

        $vehiculos = $query->orderByRaw("CASE WHEN estado = 'Autorizado' THEN 0 ELSE 1 END")
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $departamentos = Departamento::activos()
            ->orderBy('numero')
            ->get();

        $resultadoVerificacion = null;
        if (! empty($verificarPatente)) {
            $patenteLimpia = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $verificarPatente));
            $encontrado = VehiculoAutorizado::with(['departamento', 'conserje'])
                ->porPatente($patenteLimpia)
                ->first();

            $resultadoVerificacion = [
                'patente' => $patenteLimpia,
                'encontrado' => $encontrado !== null,
                'autorizado' => $encontrado ? $encontrado->estaAutorizado() : false,
                'vehiculo' => $encontrado,
            ];
        }

        $totalAutorizados = VehiculoAutorizado::autorizados()->count();
        $totalRevocados = VehiculoAutorizado::revocados()->count();
        $totalResidentes = VehiculoAutorizado::where('tipo', 'Residente')->count();

        return view('conserje.vehiculos.index', [
            'vehiculos' => $vehiculos,
            'departamentos' => $departamentos,
            'filtro' => $filtro,
            'busqueda' => $busqueda,
            'verificarPatente' => $verificarPatente,
            'resultadoVerificacion' => $resultadoVerificacion,
            'metricas' => [
                'total_autorizados' => $totalAutorizados,
                'total_revocados' => $totalRevocados,
                'total_residentes' => $totalResidentes,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'departamento_id' => ['required', 'exists:departamentos,id'],
            'patente' => ['required', 'string', 'max:15'],
            'marca' => ['nullable', 'string', 'max:60'],
            'modelo' => ['nullable', 'string', 'max:60'],
            'color' => ['nullable', 'string', 'max:40'],
            'tipo' => ['required', 'in:Residente,Visita Frecuente,Servicio'],
            'vigente_hasta' => ['nullable', 'date'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ], [
            'departamento_id.required' => 'Debes seleccionar el departamento al que pertenece el vehículo.',
            'departamento_id.exists' => 'El departamento seleccionado no es válido.',
            'patente.required' => 'La patente del vehículo es obligatoria.',
            'patente.max' => 'La patente no puede superar los 15 caracteres.',
            'tipo.required' => 'Debes seleccionar el tipo de autorización.',
            'tipo.in' => 'El tipo de vehículo seleccionado no es válido.',
            'vigente_hasta.date' => 'La fecha de vigencia no es válida.',
            'observaciones.max' => 'Las observaciones no pueden superar los 500 caracteres.',
        ]);

        $departamento = Departamento::findOrFail($datos['departamento_id']);

        if (! $departamento->estaActivo()) {
            return back()
                ->withInput()
                ->withErrors(['departamento_id' => 'No es posible registrar vehículos para un departamento inactivo.']);
        }

        $patenteLimpia = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $datos['patente']));

        if (strlen($patenteLimpia) < 4 || strlen($patenteLimpia) > 10) {
            return back()
                ->withInput()
                ->withErrors(['patente' => 'El formato de patente no es válido (debe contener entre 4 y 10 caracteres alfanuméricos).']);
        }

        $vehiculo = VehiculoAutorizado::create([
            'departamento_id' => $departamento->id,
            'conserje_id' => $request->user()?->id,
            'patente' => $patenteLimpia,
            'marca' => ! empty($datos['marca']) ? trim($datos['marca']) : null,
            'modelo' => ! empty($datos['modelo']) ? trim($datos['modelo']) : null,
            'color' => ! empty($datos['color']) ? trim($datos['color']) : null,
            'tipo' => $datos['tipo'],
            'estado' => 'Autorizado',
            'vigente_hasta' => ! empty($datos['vigente_hasta']) ? $datos['vigente_hasta'] : null,
            'observaciones' => ! empty($datos['observaciones']) ? trim($datos['observaciones']) : null,
        ]);

        return redirect()->route('conserje.vehiculos.index')
            ->with('estado', "Vehículo con patente {$vehiculo->patente} autorizado exitosamente para el departamento {$departamento->numero}.");
    }

    public function verificar(Request $request): JsonResponse|RedirectResponse
    {
        $patente = $request->input('patente') ?? $request->query('patente');

        if (empty($patente)) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Debes proporcionar una patente para verificar.'], 400);
            }
            return redirect()->route('conserje.vehiculos.index');
        }

        $patenteLimpia = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $patente));

        $vehiculo = VehiculoAutorizado::with(['departamento', 'conserje'])
            ->porPatente($patenteLimpia)
            ->first();

        $autorizado = $vehiculo ? $vehiculo->estaAutorizado() : false;

        if ($request->wantsJson()) {
            return response()->json([
                'patente' => $patenteLimpia,
                'encontrado' => $vehiculo !== null,
                'autorizado' => $autorizado,
                'estado' => $vehiculo?->estado,
                'tipo' => $vehiculo?->tipo,
                'departamento' => $vehiculo?->departamento?->numero,
                'marca' => $vehiculo?->marca,
                'modelo' => $vehiculo?->modelo,
                'color' => $vehiculo?->color,
                'vigente_hasta' => $vehiculo?->vigente_hasta?->format('Y-m-d'),
            ]);
        }

        return redirect()->route('conserje.vehiculos.index', ['verificar_patente' => $patenteLimpia]);
    }

    public function cambiarEstado(Request $request, VehiculoAutorizado $vehiculo): RedirectResponse
    {
        $nuevoEstado = $vehiculo->estado === 'Autorizado' ? 'Revocado' : 'Autorizado';
        $vehiculo->update(['estado' => $nuevoEstado]);

        return redirect()->route('conserje.vehiculos.index')
            ->with('estado', "El estado de la patente {$vehiculo->patente} fue cambiado a '{$nuevoEstado}'.");
    }
}
