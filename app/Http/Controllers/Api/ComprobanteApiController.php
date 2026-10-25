<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GastoComun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\RegistrarComprobante;

class ComprobanteApiController extends Controller
{
    public function listarGastos(Request $request): JsonResponse
    {
        $datos = $request->validate(['departamento_id' => ['nullable', 'integer', 'min:1']]);
        $departamentoId = $datos['departamento_id'] ?? null;

        $query = GastoComun::whereIn('departamento_id', $request->user()->departamentos()->select('departamentos.id'));
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

        return response()->json($gastos, 200)->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, RegistrarComprobante $registro): JsonResponse
    {
        $gastoId = $request->input('gasto_id') ?? $request->input('gasto_comun_id');
        $archivo = $request->file('file') ?? $request->file('comprobante');

        $validator = Validator::make([
            'gasto_id' => $gastoId,
            'file' => $archivo,
            'monto' => $request->input('monto'),
            'fecha_pago' => $request->input('fecha_pago'),
        ], [
            'gasto_id' => ['required', 'integer', 'min:1'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'monto' => ['nullable', 'integer', 'min:1', 'max:999999999999'],
            'fecha_pago' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
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

        $datos = $validator->validated();
        $comprobante = $registro->registrar($request->user(), (int) $datos['gasto_id'], $archivo,
            isset($datos['monto']) ? (int) $datos['monto'] : null, $datos['fecha_pago'] ?? null);

        return response()->json([
            'id' => $comprobante->id,
            'estado' => 'en_revision',
            'mensaje' => 'Comprobante recibido y registrado en revisión',
        ], 201);
    }
}
