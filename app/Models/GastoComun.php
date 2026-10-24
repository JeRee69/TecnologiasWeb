<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GastoComun extends Model
{
    protected $table = 'gastos_comunes';

    protected $fillable = ['departamento_id', 'periodo', 'monto', 'estado', 'fecha_vencimiento'];

    protected $casts = ['periodo' => 'date', 'monto' => 'integer', 'fecha_vencimiento' => 'date', 'pago_validado_at' => 'datetime'];

    public function estadoVigente(): string
    {
        if ($this->estado === 'pagado') return 'pagado';
        if ($this->estado === 'moroso' || ($this->fecha_vencimiento && $this->fecha_vencimiento->lt(today('America/Santiago')))) return 'moroso';
        return 'pendiente';
    }

    public static function resumenPara(User $usuario): array
    {
        $query = static::whereIn('departamento_id', $usuario->departamentos()->select('departamentos.id'));
        $total = (clone $query)->count();
        $porPagar = (clone $query)->where('estado', '!=', 'pagado');
        $morosos = (clone $porPagar)->where(function ($query) {
            $query->where('estado', 'moroso')->orWhere('fecha_vencimiento', '<', today('America/Santiago')->toDateString());
        })->count();
        $pendientes = (clone $porPagar)->count() - $morosos;

        return ['estado' => !$total ? 'sin_cobros' : ($morosos ? 'moroso' : ($pendientes ? 'pendiente' : 'pagado')),
            'morosos' => $morosos, 'pendientes' => $pendientes, 'pagados' => $total - $morosos - $pendientes];
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(GastoComunDetalle::class)->orderBy('id');
    }

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class);
    }
}
