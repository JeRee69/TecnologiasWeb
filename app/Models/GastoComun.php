<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function comprobantes(): HasMany
    {
        return $this->hasMany(ComprobantePago::class, 'gasto_comun_id');
    }

    public function ultimoComprobante(): HasOne
    {
        return $this->hasOne(ComprobantePago::class, 'gasto_comun_id')->latestOfMany();
    }

    public function tieneComprobanteEnRevision(): bool
    {
        return $this->comprobantes()->where('estado', 'en_revision')->exists();
    }

    // Un cobro pagado o con un comprobante aún sin revisar no admite una nueva carga.
    public function permiteCargarComprobante(): bool
    {
        return $this->estado !== 'pagado' && ! $this->tieneComprobanteEnRevision();
    }

    public function montoFormateado(): string
    {
        return '$'.number_format($this->monto, 0, ',', '.');
    }
}
