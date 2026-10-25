<?php

namespace App\Services;

use App\Models\ComprobantePago;
use App\Models\GastoComun;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class RegistrarComprobante
{
    public function registrar(User $usuario, int $gastoId, UploadedFile $archivo, ?int $monto = null, ?string $fecha = null): ComprobantePago
    {
        $ruta = null;
        try {
            return DB::transaction(function () use ($usuario, $gastoId, $archivo, $monto, $fecha, &$ruta) {
                $gasto = GastoComun::whereIn('departamento_id', $usuario->departamentos()->select('departamentos.id'))
                    ->whereKey($gastoId)->lockForUpdate()->firstOrFail();
                if ($gasto->estado === 'pagado' || $gasto->comprobantes()->where('estado', 'en_revision')->lockForUpdate()->first()) {
                    throw ValidationException::withMessages(['comprobante' => $gasto->estado === 'pagado'
                        ? 'Este gasto común ya se encuentra pagado.'
                        : 'Ya existe un comprobante en revisión para este gasto común.']);
                }
                $ruta = $archivo->store('comprobantes', 'public');
                if (!$ruta) throw new \RuntimeException('No se pudo guardar el archivo del comprobante.');
                return $gasto->comprobantes()->create([
                    'departamento_id' => $gasto->departamento_id, 'user_id' => $usuario->id,
                    'archivo_path' => $ruta, 'archivo_nombre_original' => $archivo->getClientOriginalName(),
                    'archivo_mime' => $archivo->getMimeType(), 'archivo_tamano' => $archivo->getSize(),
                    'monto' => $monto ?? $gasto->monto,
                    'fecha_pago' => $fecha ?? today('America/Santiago')->toDateString(), 'estado' => 'en_revision',
                ]);
            });
        } catch (Throwable $error) {
            if ($ruta) Storage::disk('public')->delete($ruta);
            throw $error;
        }
    }
}
