<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ComprobantePago;
use App\Models\Departamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ComprobantePagoController extends Controller
{
    public function index(Request $request)
    {
        $estadoFiltro = $request->query('estado', 'todos');
        $departamentoFiltro = $request->query('departamento_id');

        $query = ComprobantePago::with(['gastoComun', 'departamento', 'residente', 'revisor'])
            ->latest();

        if ($estadoFiltro !== 'todos' && in_array($estadoFiltro, ['en_revision', 'aprobado', 'rechazado'])) {
            $query->where('estado', $estadoFiltro);
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

        return view('admin.comprobantes.index', compact(
            'comprobantes',
            'totales',
            'departamentos',
            'estadoFiltro',
            'departamentoFiltro'
        ));
    }

    public function aprobar(Request $request, ComprobantePago $comprobante)
    {
        $comprobante->update([
            'estado' => 'aprobado',
            'motivo_rechazo' => null,
            'revisado_por' => $request->user()->id,
            'revisado_at' => now(),
        ]);

        if ($comprobante->gastoComun) {
            $comprobante->gastoComun->update([
                'estado' => 'Pagado',
            ]);
        }

        return redirect()->back()
            ->with('exito', 'Comprobante aprobado exitosamente. El gasto común fue actualizado a estado "Pagado".');
    }

    public function rechazar(Request $request, ComprobantePago $comprobante)
    {
        $request->validate([
            'motivo_rechazo' => ['required', 'string', 'min:3', 'max:500'],
        ], [
            'motivo_rechazo.required' => 'Debes ingresar el motivo del rechazo.',
            'motivo_rechazo.min' => 'El motivo debe tener al menos 3 caracteres.',
            'motivo_rechazo.max' => 'El motivo no puede exceder los 500 caracteres.',
        ]);

        $comprobante->update([
            'estado' => 'rechazado',
            'motivo_rechazo' => $request->motivo_rechazo,
            'revisado_por' => $request->user()->id,
            'revisado_at' => now(),
        ]);

        if ($comprobante->gastoComun) {
            $comprobante->gastoComun->update([
                'estado' => 'Pendiente',
            ]);
        }

        return redirect()->back()
            ->with('exito', 'Comprobante rechazado. El gasto común se mantiene en estado "Pendiente" y el residente podrá ver el motivo.');
    }

    public function verArchivo(ComprobantePago $comprobante)
    {
        if (! Storage::disk('public')->exists($comprobante->archivo_path)) {
            abort(404, 'El archivo del comprobante no fue encontrado en el servidor.');
        }

        return Storage::disk('public')->response(
            $comprobante->archivo_path,
            $comprobante->archivo_nombre_original
        );
    }
}
