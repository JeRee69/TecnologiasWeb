<?php

namespace App\Http\Controllers\Residente;

use App\Http\Controllers\Controller;
use App\Models\EspacioComun;
use App\Models\Reserva;
use App\Services\DisponibilidadEspacio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ReservaController extends Controller
{
    public function index(Request $request): Response
    {
        return response()->view('residente.reservas.index', [
            'reservas' => Reserva::with(['espacioComun', 'eventos'])->where('user_id', $request->user()->id)
                ->orderByDesc('fecha')->orderByDesc('hora_inicio')->orderByDesc('id')->paginate(10),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, DisponibilidadEspacio $disponibilidad): RedirectResponse
    {
        $datos = $request->validateWithBag('reserva', [
            'espacio_id' => ['required', 'integer', 'min:1'],
            'fecha' => ['required', 'date_format:Y-m-d'],
            'bloque' => ['required', 'string', 'regex:/^([01][0-9]|2[0-3]):[0-5][0-9]–([01][0-9]|2[0-3]):[0-5][0-9]$/u'],
        ], [
            'espacio_id.required' => 'Selecciona un espacio común.',
            'espacio_id.integer' => 'Selecciona un espacio válido.',
            'espacio_id.min' => 'Selecciona un espacio válido.',
            'fecha.required' => 'Selecciona una fecha.',
            'fecha.date_format' => 'Selecciona una fecha válida (AAAA-MM-DD).',
            'bloque.required' => 'Selecciona un horario disponible antes de enviar la solicitud.',
            'bloque.string' => 'Selecciona un bloque válido.',
            'bloque.regex' => 'Selecciona un bloque válido del calendario.',
        ]);

        $reserva = DB::transaction(function () use ($request, $datos, $disponibilidad) {
            // Serializa las confirmaciones del mismo espacio incluso cuando aún no hay reservas.
            // También coordina con las actualizaciones de estado y horario del administrador.
            $espacio = EspacioComun::whereKey($datos['espacio_id'])->lockForUpdate()->first();
            $bloque = $espacio ? collect($disponibilidad->bloques($espacio, $datos['fecha']))
                ->first(fn ($bloque) => $bloque['inicio'].'–'.$bloque['fin'] === $datos['bloque']) : null;

            if (! $bloque || $bloque['estado'] !== 'disponible') {
                throw ValidationException::withMessages([
                    'bloque' => $bloque && $bloque['estado'] === 'ocupado'
                        ? 'El horario ya no está disponible: coincide total o parcialmente con una reserva existente. Vuelve a consultar y selecciona otro bloque.'
                        : 'El espacio o el horario ya no está disponible. Vuelve a consultar y selecciona otro bloque.',
                ])->errorBag('reserva');
            }

            $nueva = Reserva::create([
                'espacio_comun_id' => $espacio->id,
                'user_id' => $request->user()->id,
                'fecha' => $datos['fecha'],
                'hora_inicio' => $bloque['inicio'],
                'hora_fin' => $bloque['fin'],
                'estado' => 'pendiente',
            ]);
            $nueva->eventos()->create(['user_id' => $request->user()->id, 'estado_anterior' => null,
                'estado_nuevo' => 'pendiente', 'observacion' => 'Solicitud enviada por el residente.']);
            return $nueva;
        }, 3);

        return redirect()->route('residente.reservas.index')
            ->with('estado', "Tu solicitud #{$reserva->id} fue enviada. Queda pendiente de aprobación del administrador.");
    }

    public function cancelar(Request $request, string $reserva): RedirectResponse
    {
        // No revelar reservas ajenas, ni siquiera si se conoce su identificador.
        $propia = Reserva::where('user_id', $request->user()->id)->findOrFail($reserva);
        $datos = $request->validateWithBag('cancelacion', [
            'motivo_cancelacion' => ['required', 'string', 'max:1000'],
        ], [
            'motivo_cancelacion.required' => 'Indica la justificación de la cancelación.',
            'motivo_cancelacion.string' => 'Ingresa una justificación válida.',
            'motivo_cancelacion.max' => 'La justificación no puede superar los 1000 caracteres.',
        ]);

        DB::transaction(function () use ($propia, $request, $datos) {
            // Mismo orden de bloqueo que la confirmación: espacio y después reserva.
            EspacioComun::whereKey($propia->espacio_comun_id)->lockForUpdate()->firstOrFail();
            $actual = Reserva::where('user_id', $request->user()->id)->lockForUpdate()->findOrFail($propia->id);
            if (! $actual->puedeCancelarse()) {
                throw ValidationException::withMessages([
                    'reserva' => 'Solo puedes cancelar reservas pendientes o confirmadas que aún no hayan comenzado.',
                ])->errorBag('cancelacion');
            }
            $anterior = $actual->estado;
            $actual->estado = 'cancelada';
            $actual->motivo_cancelacion = $datos['motivo_cancelacion'];
            $actual->cancelada_at = now();
            $actual->save();
            $actual->eventos()->create(['user_id' => $request->user()->id, 'estado_anterior' => $anterior,
                'estado_nuevo' => 'cancelada', 'observacion' => $datos['motivo_cancelacion']]);
        }, 3);

        return redirect()->route('residente.reservas.index')
            ->with('estado', "Tu reserva #{$propia->id} fue cancelada. El horario quedó liberado.");
    }
}
