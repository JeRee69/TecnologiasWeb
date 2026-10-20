<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EspacioComun;
use App\Models\Reserva;
use App\Services\DisponibilidadEspacio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class ReservaController extends Controller
{
    public function index(Request $request): Response
    {
        $validator = Validator::make($request->query(), [
            'espacio_id' => ['nullable', 'integer', Rule::exists('espacios_comunes', 'id')],
            'fecha' => ['nullable', 'date_format:Y-m-d'],
            'estado' => ['nullable', 'string', Rule::in(array_keys(Reserva::ESTADOS))],
        ], [
            'espacio_id.integer' => 'Selecciona un espacio válido.',
            'espacio_id.exists' => 'El espacio seleccionado no existe.',
            'fecha.date_format' => 'Selecciona una fecha válida (AAAA-MM-DD).',
            'estado.string' => 'Selecciona un estado válido.',
            'estado.in' => 'Selecciona un estado válido.',
        ]);
        $valido = !$validator->fails();
        $filtros = $valido ? $validator->validated() : [];
        $consulta = Reserva::with(['espacioComun', 'usuario:id,nombre,email', 'eventos.usuario:id,nombre']);
        if (!$valido) { $consulta->whereRaw('1 = 0'); }
        if (!empty($filtros['espacio_id'])) { $consulta->where('espacio_comun_id', $filtros['espacio_id']); }
        if (!empty($filtros['fecha'])) { $consulta->whereDate('fecha', $filtros['fecha']); }
        if (!empty($filtros['estado'])) { $consulta->where('estado', $filtros['estado']); }

        return response()->view('admin.reservas.index', [
            'reservas' => $consulta->orderByDesc('fecha')->orderByDesc('hora_inicio')->orderByDesc('id')->paginate(10)->appends($filtros),
            'espacios' => EspacioComun::orderBy('nombre')->get(['id', 'nombre', 'estado']),
            'filtros' => $filtros,
            'pendientes' => Reserva::where('estado', 'pendiente')->count(),
            'errors' => ($request->session()->get('errors', new ViewErrorBag))->put('default', $validator->errors()),
        ], $valido ? 200 : 422)->header('Cache-Control', 'private, no-store');
    }

    public function actualizarEstado(Request $request, string $reserva, DisponibilidadEspacio $disponibilidad): RedirectResponse
    {
        $referencia = Reserva::findOrFail($reserva);
        $datos = $request->validateWithBag('revision', [
            'estado' => ['required', 'string', Rule::in(['confirmada', 'rechazada', 'cancelada'])],
            'observacion' => ['required_if:estado,rechazada,cancelada', 'nullable', 'string', 'max:1000'],
        ], [
            'estado.required' => 'Selecciona una acción.',
            'estado.string' => 'Selecciona una acción válida.',
            'estado.in' => 'Selecciona aprobar, rechazar o cancelar.',
            'observacion.required_if' => 'Indica una justificación para rechazar o cancelar.',
            'observacion.string' => 'Ingresa una observación válida.',
            'observacion.max' => 'La observación no puede superar los 1000 caracteres.',
        ]);

        DB::transaction(function () use ($referencia, $request, $datos, $disponibilidad) {
            $espacio = EspacioComun::whereKey($referencia->espacio_comun_id)->lockForUpdate()->firstOrFail();
            $actual = Reserva::lockForUpdate()->findOrFail($referencia->id);
            $destino = $datos['estado'];
            $permitido = ($actual->estado === 'pendiente' && in_array($destino, ['confirmada', 'rechazada'], true))
                || ($actual->estado === 'confirmada' && $destino === 'cancelada' && $actual->puedeCancelarse());
            if (!$permitido) {
                throw ValidationException::withMessages(['estado' => 'La solicitud cambió de estado o esta acción no está permitida. Actualiza el listado.'])->errorBag('revision');
            }
            if ($destino === 'confirmada') {
                $bloque = collect($disponibilidad->bloques($espacio, $actual->fecha->toDateString(), $actual->id))
                    ->first(fn ($bloque) => $actual->hora_inicio === $bloque['inicio'].':00'
                        && $actual->hora_fin === $bloque['fin'].':00' && $bloque['estado'] === 'disponible');
                if (!$bloque || !$actual->usuario->estaActivo() || !$actual->usuario->esResidente()) {
                    throw ValidationException::withMessages(['estado' => 'No se puede aprobar: revisa disponibilidad, horario, configuración del espacio y estado del residente.'])->errorBag('revision');
                }
            }
            $anterior = $actual->estado;
            $actual->estado = $destino;
            if ($destino === 'cancelada') {
                $actual->motivo_cancelacion = $datos['observacion'];
                $actual->cancelada_at = now();
            }
            $actual->save();
            $actual->eventos()->create(['user_id' => $request->user()->id, 'estado_anterior' => $anterior,
                'estado_nuevo' => $destino, 'observacion' => $datos['observacion'] ?? null]);
        }, 3);

        return redirect()->route('admin.reservas.index', ['estado' => 'pendiente'])
            ->with('estado', 'Estado actualizado correctamente.');
    }
}
