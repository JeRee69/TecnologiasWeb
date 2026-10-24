<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ComprobantePago;
use App\Models\GastoComun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ComprobanteApiController extends Controller
{
    public function listarGastos(Request $request): JsonResponse
    {
        $departamentoId = $request->query('departamento_id');

        $query = GastoComun::query();
        if ($departamentoId) {
            $query->where('departamento_id', $departamentoId);
        }

        $gastos = $query->orderByDesc('periodo')->get()->map(function (GastoComun $gasto) {
            return [
                'id' => $gasto->id,
                'periodo' => $gasto->periodo?->format('Y-m'),
                'monto' => $gasto->monto,
                'estado' => $gasto->estadoVigente(),
            ];
        });

        return response()->json($gastos, 200);
    }

    public function store(Request $request): JsonResponse
    {
        $gastoId = $request->input('gasto_id') ?? $request->input('gasto_comun_id');
        $archivo = $request->file('file') ?? $request->file('comprobante');

        $validator = Validator::make([
            'gasto_id' => $gastoId,
            'file' => $archivo,
        ], [
            'gasto_id' => ['required', 'exists:gastos_comunes,id'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'gasto_id.required' => 'El identificador del gasto es obligatorio.',
            'gasto_id.exists' => 'El gasto común indicado no existe.',
            'file.required' => 'El archivo de comprobante es obligatorio.',
            'file.mimes' => 'El archivo debe tener formato PDF, JPG, JPEG o PNG.',
            'file.max' => 'El tamaño del archivo no puede exceder 5 MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'mensaje' => 'Datos de comprobante inválidos',
                'errores' => $validator->errors(),
            ], 422);
        }

        $gasto = GastoComun::findOrFail($gastoId);
        $rutaArchivo = $archivo->store('comprobantes', 'public');

        $usuarioId = $request->user()?->id ?? 3;

        $comprobante = ComprobantePago::create([
            'gasto_comun_id' => $gasto->id,
            'departamento_id' => $gasto->departamento_id,
            'user_id' => $usuarioId,
            'archivo_path' => $rutaArchivo,
            'archivo_nombre_original' => $archivo->getClientOriginalName(),
            'archivo_mime' => $archivo->getMimeType() ?: $archivo->getClientMimeType(),
            'archivo_tamano' => $archivo->getSize(),
            'monto' => $gasto->monto,
            'fecha_pago' => today('America/Santiago')->toDateString(),
            'estado' => 'en_revision',
        ]);

        return response()->json([
            'id' => $comprobante->id,
            'estado' => 'en_revision',
            'mensaje' => 'Comprobante recibido y registrado en revisión',
        ], 201);
    }
}
