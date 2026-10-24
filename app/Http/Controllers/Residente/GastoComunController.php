<?php

namespace App\Http\Controllers\Residente;

use App\Http\Controllers\Controller;
use App\Models\ComprobantePago;
use App\Models\GastoComun;
use App\Services\GastoComunPdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class GastoComunController extends Controller
{
    public function descargarPdf(Request $request, int $gasto, GastoComunPdf $generador): Response
    {
        // Autoriza dentro de la consulta: un cobro ajeno y uno inexistente devuelven el mismo 404.
        $cobro = GastoComun::whereIn('departamento_id', $request->user()->departamentos()->select('departamentos.id'))
            ->with(['departamento', 'detalles'])->findOrFail($gasto);
        $nombre = 'gasto-comun-'.$cobro->periodo->format('Y-m').'-'.$cobro->id.'.pdf';

        return response($generador->generar($cobro), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$nombre.'"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function index(Request $request): Response
    {
        // La asociación vigente, incluso de unidades inactivas, define qué cobros puede consultar.
        $departamentos = $request->user()->departamentos()->orderBy('numero')->get();
        $gastos = GastoComun::with(['departamento', 'detalles', 'ultimoComprobante'])
            ->whereIn('departamento_id', $departamentos->pluck('id'))
            ->orderByDesc('periodo')->orderByDesc('id')->paginate(10);

        $resumenGastos = GastoComun::resumenPara($request->user());

        // Rechazos cuyo cobro sigue impago, para que el residente pueda corregir su pago.
        $comprobantesRechazados = ComprobantePago::with('gastoComun')
            ->whereIn('departamento_id', $departamentos->pluck('id'))
            ->where('estado', 'rechazado')
            ->whereHas('gastoComun', fn ($query) => $query->where('estado', '!=', 'pagado'))
            ->latest('revisado_at')->get();

        return response()->view('residente.gastos-comunes.index', compact('departamentos', 'gastos', 'resumenGastos', 'comprobantesRechazados'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function storeComprobante(Request $request, int $gasto): RedirectResponse
    {
        $cobro = GastoComun::whereIn('departamento_id', $request->user()->departamentos()->select('departamentos.id'))
            ->findOrFail($gasto);

        if (! $cobro->permiteCargarComprobante()) {
            return back()->withErrors([
                'comprobante' => $cobro->estado === 'pagado'
                    ? 'Este gasto común ya se encuentra pagado.'
                    : 'Ya existe un comprobante en revisión para este gasto común.',
            ]);
        }

        $datos = $request->validate([
            'comprobante' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'monto' => ['nullable', 'integer', 'min:1', 'max:999999999999'],
            'fecha_pago' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ], [
            'comprobante.required' => 'Debes adjuntar el archivo del comprobante de transferencia.',
            'comprobante.file' => 'El archivo adjunto no es válido.',
            'comprobante.uploaded' => 'No se pudo subir el archivo. Verifica que no supere los 5 MB.',
            'comprobante.mimes' => 'El comprobante debe ser un archivo PDF, JPG, JPEG o PNG.',
            'comprobante.max' => 'El comprobante no puede superar el tamaño máximo de 5 MB.',
            'monto.integer' => 'El monto informado debe ser un número entero en CLP.',
            'fecha_pago.date_format' => 'La fecha de pago debe tener un formato válido.',
            'fecha_pago.before_or_equal' => 'La fecha de pago no puede ser futura.',
        ]);

        $archivo = $request->file('comprobante');
        $ruta = $archivo->store('comprobantes', 'public');

        ComprobantePago::create([
            'gasto_comun_id' => $cobro->id,
            'departamento_id' => $cobro->departamento_id,
            'user_id' => $request->user()->id,
            'archivo_path' => $ruta,
            'archivo_nombre_original' => $archivo->getClientOriginalName(),
            'archivo_mime' => $archivo->getMimeType() ?: $archivo->getClientMimeType(),
            'archivo_tamano' => $archivo->getSize(),
            'monto' => $datos['monto'] ?? $cobro->monto,
            'fecha_pago' => $datos['fecha_pago'] ?? today('America/Santiago')->toDateString(),
            'estado' => 'en_revision',
        ]);

        return redirect()->route('residente.gastos-comunes.index')
            ->with('estado', 'Comprobante cargado. Quedó asociado al cobro con estado "En revisión".');
    }

    public function verArchivo(Request $request, int $comprobante)
    {
        $registro = ComprobantePago::whereIn('departamento_id', $request->user()->departamentos()->select('departamentos.id'))
            ->findOrFail($comprobante);

        if (! Storage::disk('public')->exists($registro->archivo_path)) {
            abort(404, 'El archivo solicitado no se encuentra disponible.');
        }

        return Storage::disk('public')->response($registro->archivo_path, $registro->archivo_nombre_original);
    }
}
