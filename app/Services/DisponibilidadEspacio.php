<?php

namespace App\Services;

use App\Models\EspacioComun;
use Carbon\CarbonImmutable;

class DisponibilidadEspacio
{
    /** Bloques diarios de 60 minutos; el último termina a la hora de cierre. */
    public function bloques(EspacioComun $espacio, string $fecha, ?int $excluirReserva = null): array
    {
        if ($espacio->estado !== 'activo' || ! $espacio->tieneConfiguracion()) {
            return [];
        }

        // Solo leer los horarios: nunca exponer datos del residente que reservó.
        $reservas = $espacio->reservas()->vigentes()->whereDate('fecha', $fecha)
            ->when($excluirReserva !== null, fn ($query) => $query->where('id', '!=', $excluirReserva))
            ->get(['hora_inicio', 'hora_fin']);
        $minutos = fn (string $hora): int => (int) substr($hora, 0, 2) * 60 + (int) substr($hora, 3, 2);
        $formato = fn (int $valor): string => sprintf('%02d:%02d', intdiv($valor, 60), $valor % 60);
        $cierre = $minutos($espacio->hora_cierre);
        $ahora = CarbonImmutable::now(config('app.timezone'));
        $bloques = [];

        for ($inicio = $minutos($espacio->hora_apertura); $inicio < $cierre; $inicio = $fin) {
            $fin = min($inicio + 60, $cierre);
            // Intervalos semiabiertos [inicio, fin). Conservar segundos de las reservas
            // evita ofrecer un bloque que aún está ocupado durante parte de su primer minuto.
            $ocupado = $reservas->contains(fn ($reserva) =>
                $reserva->hora_inicio < $formato($fin).':00'
                && $reserva->hora_fin > $formato($inicio).':00');
            $pasado = $fecha < $ahora->toDateString()
                || ($fecha === $ahora->toDateString() && $formato($inicio) <= $ahora->format('H:i'));
            $bloques[] = [
                'inicio' => $formato($inicio), 'fin' => $formato($fin),
                'estado' => $ocupado ? 'ocupado' : ($pasado ? 'pasado' : 'disponible'),
            ];
        }

        return $bloques;
    }
}
