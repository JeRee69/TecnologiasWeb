<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ComprobantePago;
use App\Models\Departamento;
use App\Models\GastoComun;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ComprobantePagoController extends Controller
{
    public function index(Request $request)
    {
        $estadoFiltro = $request->query('estado', 'en_revision');
        $departamentoFiltro = $request->query('departamento_id');

        $query = ComprobantePago::with(['gastoComun', 'departamento', 'residente', 'revisor'])->latest();

        if (in_array($estadoFiltro, ['en_revision', 'aprobado', 'rechazado'], true)) {
            $query->where('estado', $estadoFiltro);
        } else {
            $estadoFiltro = 'todos';
        }

        if ($departamentoFiltro) {
            $query->where('departamento_id', $departamentoFiltro);
        }

        $comprobantes = $query->paginate(15)->withQueryString();

        $totales = [
            'total' => ComprobantePago::count(),
            'en_revision' => ComprobantePago::where('estado', 'en_revision')->count(),
            'aprobados' => ComprobantePago::where('estado', 'aprobado')->count(),
            'rechazados' => ComprobantePago::where('estado', 'rechazado')->count(),
        ];

        $departamentos = Departamento::orderBy('numero')->get();

        return response()->view('admin.comprobantes.index', compact(
            'comprobantes', 'totales', 'departamentos', 'estadoFiltro', 'departamentoFiltro'
        ))->header('Cache-Control', 'private, no-store');
    }

    public function aprobar(Request $request, ComprobantePago $comprobante): RedirectResponse
    {
        DB::transaction(function () use ($request, $comprobante) {
            // Mismo orden de bloqueo que la carga: cobro antes del comprobante.
            $gasto = GastoComun::whereKey($comprobante->gasto_comun_id)->lockForUpdate()->firstOrFail();
            $actual = ComprobantePago::whereKey($comprobante->id)->lockForUpdate()->firstOrFail();
            if ($actual->estado !== 'en_revision') {
                throw ValidationException::withMessages(['comprobante' => 'Este comprobante ya fue revisado.']);
            }
            if ($gasto->estado === 'pagado') {
                throw ValidationException::withMessages(['comprobante' => 'El gasto común ya tiene un pago validado.']);
            }
            if ($actual->monto !== $gasto->monto) {
                throw ValidationException::withMessages(['comprobante' => 'El monto del comprobante debe coincidir exactamente con el total del cobro. Rechaza el comprobante indicando la diferencia para que el residente lo corrija.']);
            }

            $actual->update([
                'estado' => 'aprobado',
                'motivo_rechazo' => null,
                'revisado_por' => $request->user()->id,
                'revisado_at' => now(),
            ]);

            $gasto->estado = 'pagado';
            $gasto->pago_validado_at = now();
            $gasto->pago_validado_por = $request->user()->id;
            $gasto->referencia_pago = 'Comprobante #'.$actual->id;
            $gasto->save();
        }, 3);

        return back()->with('estado', 'Comprobante aprobado. El gasto común quedó Al día (Pagado).');
    }

    public function rechazar(Request $request, ComprobantePago $comprobante): RedirectResponse
    {
        $datos = $request->validate([
            'motivo_rechazo' => ['required', 'string', 'min:3', 'max:500'],
        ], [
            'motivo_rechazo.required' => 'Debes ingresar el motivo del rechazo.',
            'motivo_rechazo.min' => 'El motivo debe tener al menos 3 caracteres.',
            'motivo_rechazo.max' => 'El motivo no puede exceder los 500 caracteres.',
        ]);

        DB::transaction(function () use ($request, $comprobante, $datos) {
            $actual = ComprobantePago::whereKey($comprobante->id)->lockForUpdate()->firstOrFail();
            if ($actual->estado !== 'en_revision') {
                throw ValidationException::withMessages(['comprobante' => 'Este comprobante ya fue revisado.']);
            }

            // El gasto no se modifica: conserva su estado pendiente o moroso.
            $actual->update([
                'estado' => 'rechazado',
                'motivo_rechazo' => $datos['motivo_rechazo'],
                'revisado_por' => $request->user()->id,
                'revisado_at' => now(),
            ]);
        }, 3);

        return back()->with('estado', 'Comprobante rechazado. El gasto común se mantiene pendiente y el residente verá el motivo.');
    }

    public function verArchivo(ComprobantePago $comprobante)
    {
        if (! Storage::disk('public')->exists($comprobante->archivo_path)) {
            abort(404, 'El archivo del comprobante no fue encontrado en el servidor.');
        }

        return Storage::disk('public')->response($comprobante->archivo_path, $comprobante->archivo_nombre_original);
    }
}
