<?php

namespace App\Http\Controllers\Residente;

use App\Http\Controllers\Controller;
use App\Models\ComprobantePago;
use App\Models\GastoComun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class GastoComunController extends Controller
{
    public function index(Request $request)
    {
        $usuario = $request->user();
        $departamentosIds = $usuario->departamentos()->pluck('departamentos.id');

        $gastos = GastoComun::with(['departamento', 'comprobantes' => function ($q) {
            $q->latest();
        }])
            ->whereIn('departamento_id', $departamentosIds)
            ->orderBy('periodo', 'desc')
            ->get();

        $comprobantesRechazados = ComprobantePago::with(['gastoComun', 'departamento'])
            ->whereIn('departamento_id', $departamentosIds)
            ->where('estado', 'rechazado')
            ->latest()
            ->get();

        return view('residente.gastos-comunes.index', compact('usuario', 'gastos', 'comprobantesRechazados'));
    }

    public function storeComprobante(Request $request, GastoComun $gasto)
    {
        $usuario = $request->user();
        $departamentosIds = $usuario->departamentos()->pluck('departamentos.id');

        if (! $departamentosIds->contains($gasto->departamento_id)) {
            abort(403, 'No tienes autorización para cargar comprobantes en este departamento.');
        }

        if (! $gasto->permiteCargarComprobante()) {
            return back()->withErrors([
                'comprobante' => 'Este gasto común ya se encuentra pagado.',
            ]);
        }

        $request->validate([
            'comprobante' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'monto' => ['nullable', 'numeric', 'min:1'],
            'fecha_pago' => ['nullable', 'date'],
        ], [
            'comprobante.required' => 'Debes adjuntar el archivo del comprobante de transferencia.',
            'comprobante.file' => 'El archivo adjunto no es válido.',
            'comprobante.mimes' => 'El comprobante debe ser un archivo en formato PDF, JPG, JPEG o PNG.',
            'comprobante.max' => 'El comprobante no puede superar el tamaño máximo de 5 MB.',
            'monto.numeric' => 'El monto informado debe ser numérico.',
            'fecha_pago.date' => 'La fecha de pago debe tener un formato de fecha válido.',
        ]);

        $archivo = $request->file('comprobante');
        $rutaArchivo = $archivo->store('comprobantes', 'public');

        $comprobante = ComprobantePago::create([
            'gasto_comun_id' => $gasto->id,
            'departamento_id' => $gasto->departamento_id,
            'user_id' => $usuario->id,
            'archivo_path' => $rutaArchivo,
            'archivo_nombre_original' => $archivo->getClientOriginalName(),
            'archivo_mime' => $archivo->getClientMimeType() ?: $archivo->getMimeType(),
            'archivo_tamano' => $archivo->getSize(),
            'monto' => $request->filled('monto') ? (int) $request->monto : $gasto->monto_total,
            'fecha_pago' => $request->filled('fecha_pago') ? $request->fecha_pago : now()->toDateString(),
            'estado' => 'en_revision',
        ]);

        $gasto->update([
            'estado' => 'En revisión',
        ]);

        return redirect()->route('residente.gastos-comunes.index')
            ->with('exito', 'Comprobante de pago cargado exitosamente. Se encuentra en estado "En revisión".');
    }

    public function verArchivo(Request $request, ComprobantePago $comprobante)
    {
        $usuario = $request->user();
        $departamentosIds = $usuario->departamentos()->pluck('departamentos.id');

        if (! $departamentosIds->contains($comprobante->departamento_id) && $comprobante->user_id !== $usuario->id) {
            abort(403, 'No estás autorizado para consultar este comprobante.');
        }

        if (! Storage::disk('public')->exists($comprobante->archivo_path)) {
            abort(404, 'El archivo solicitado no se encuentra disponible.');
        }

        return Storage::disk('public')->response(
            $comprobante->archivo_path,
            $comprobante->archivo_nombre_original
        );
    }
}
