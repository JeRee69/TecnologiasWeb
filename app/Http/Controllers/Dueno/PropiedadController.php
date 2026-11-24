<?php

namespace App\Http\Controllers\Dueno;

use App\Http\Controllers\Controller;
use App\Models\Departamento;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PropiedadController extends Controller
{
    public function index(Request $request): Response
    {
        $usuario = $request->user();

        $propiedades = $usuario->propiedades()
            ->withCount([
                'residentes',
                'gastosComunes as gastos_por_pagar_count' => fn ($q) => $q->where('estado', '!=', 'pagado'),
                'comprobantesPago as comprobantes_en_revision_count' => fn ($q) => $q->where('estado', 'en_revision'),
                'incidencias as incidencias_abiertas_count' => fn ($q) => $q->whereIn('estado', ['Pendiente', 'En Proceso']),
            ])
            ->withSum(['gastosComunes as deuda_total' => fn ($q) => $q->where('estado', '!=', 'pagado')], 'monto')
            ->orderBy('piso')
            ->orderBy('numero')
            ->get();

        $metricas = [
            'propiedades' => $propiedades->count(),
            'residentes' => $propiedades->sum('residentes_count'),
            'por_pagar' => $propiedades->sum('gastos_por_pagar_count'),
            'incidencias' => $propiedades->sum('incidencias_abiertas_count'),
        ];

        return response()->view('dueno.index', compact('usuario', 'propiedades', 'metricas'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function show(Request $request, Departamento $departamento): Response
    {
        abort_unless($departamento->propietario_id === $request->user()->id, 403);

        $departamento->load([
            'residentes' => fn ($q) => $q->orderByPivot('es_titular', 'desc')->orderBy('nombre'),
        ]);

        $gastos = $departamento->gastosComunes()
            ->with('ultimoComprobante')
            ->orderByDesc('periodo')
            ->get();

        $comprobantes = $departamento->comprobantesPago()
            ->with(['gastoComun', 'residente'])
            ->orderByDesc('created_at')
            ->get();

        $incidencias = $departamento->incidencias()
            ->with('residente')
            ->orderByDesc('created_at')
            ->get();

        return response()->view('dueno.show', compact('departamento', 'gastos', 'comprobantes', 'incidencias'))
            ->header('Cache-Control', 'private, no-store');
    }
}
