<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Departamento;
use App\Models\Encomienda;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EncomiendaApiController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validador = Validator::make($request->all(), [
            'departamento_id' => ['required', 'exists:departamentos,id'],
            'empresa_delivery' => ['required', 'string', 'max:100'],
            'fecha_ingreso' => ['nullable', 'date'],
        ]);

        if ($validador->fails()) {
            return response()->json([
                'mensaje' => 'Datos de encomienda inválidos',
                'errores' => $validador->errors(),
            ], 400);
        }

        $departamento = Departamento::find($request->departamento_id);

        if (! $departamento || ! $departamento->estaActivo()) {
            return response()->json([
                'mensaje' => 'El departamento indicado no existe o se encuentra inactivo',
            ], 400);
        }

        $fechaIngreso = $request->filled('fecha_ingreso')
            ? $request->date('fecha_ingreso')
            : now();

        $encomienda = Encomienda::create([
            'departamento_id' => $departamento->id,
            'empresa_delivery' => trim($request->empresa_delivery),
            'fecha_ingreso' => $fechaIngreso,
            'estado' => 'Ingresado',
            'codigo_seguimiento' => $request->codigo_seguimiento ? trim($request->codigo_seguimiento) : null,
            'descripcion' => $request->descripcion ? trim($request->descripcion) : null,
            'conserje_id' => $request->user()?->id,
        ]);

        return response()->json([
            'id' => $encomienda->id,
            'estado' => 'ingresado',
        ], 201);
    }
}
