<?php

namespace App\Http\Controllers\Residente;

use App\Http\Controllers\Controller;
use App\Models\EspacioComun;
use App\Services\DisponibilidadEspacio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ViewErrorBag;
use Symfony\Component\HttpFoundation\Response;

class DisponibilidadController extends Controller
{
    public function index(Request $request, DisponibilidadEspacio $disponibilidad): Response
    {
        $validator = Validator::make($request->query(), [
            'espacio_id' => ['nullable', 'integer', 'min:1'],
            'fecha' => ['nullable', 'date_format:Y-m-d'],
        ], [
            'espacio_id.integer' => 'Selecciona un espacio válido.',
            'espacio_id.min' => 'Selecciona un espacio válido.',
            'fecha.date_format' => 'Selecciona una fecha válida (AAAA-MM-DD).',
        ]);
        $valido = ! $validator->fails();
        $datos = $valido ? $validator->validated() : [];
        $espacios = EspacioComun::activos()->orderBy('nombre')->get();
        $espacio = ! empty($datos['espacio_id'])
            ? $espacios->firstWhere('id', (int) $datos['espacio_id']) : $espacios->first();
        if ($valido && ! empty($datos['espacio_id'])) {
            abort_unless($espacio, 404);
        }
        $fecha = $datos['fecha'] ?? now()->toDateString();

        return response()->view('residente.espacios-comunes.disponibilidad', [
            'espacios' => $espacios, 'espacio' => $espacio, 'fecha' => $fecha,
            'bloques' => $valido && $espacio ? $disponibilidad->bloques($espacio, $fecha) : [],
            'errors' => ($request->session()->get('errors', new ViewErrorBag))->put('default', $validator->errors()),
        ], $valido ? 200 : 422)->header('Cache-Control', 'private, no-store');
    }
}
