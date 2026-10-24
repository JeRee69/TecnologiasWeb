<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComprobantePago extends Model
{
    use HasFactory;

    protected $table = 'comprobantes_pago';

    protected $fillable = [
        'gasto_comun_id',
        'departamento_id',
        'user_id',
        'archivo_path',
        'archivo_nombre_original',
        'archivo_mime',
        'archivo_tamano',
        'monto',
        'fecha_pago',
        'estado',
        'motivo_rechazo',
        'revisado_por',
        'revisado_at',
    ];

    protected $casts = [
        'fecha_pago' => 'date',
        'revisado_at' => 'datetime',
        'monto' => 'integer',
        'archivo_tamano' => 'integer',
    ];

    public function gastoComun(): BelongsTo
    {
        return $this->belongsTo(GastoComun::class, 'gasto_comun_id');
    }

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class, 'departamento_id');
    }

    public function residente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function revisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }

    public function estadoLegible(): string
    {
        return match ($this->estado) {
            'en_revision' => 'En revisión',
            'aprobado' => 'Aprobado',
            'rechazado' => 'Rechazado',
            default => ucfirst($this->estado),
        };
    }

    public function montoFormateado(): string
    {
        $monto = $this->monto ?? $this->gastoComun?->monto ?? 0;
        return '$'.number_format($monto, 0, ',', '.');
    }

    public function tamanoLegible(): string
    {
        if ($this->archivo_tamano < 1024) {
            return $this->archivo_tamano . ' B';
        } elseif ($this->archivo_tamano < 1048576) {
            return round($this->archivo_tamano / 1024, 1) . ' KB';
        }
        return round($this->archivo_tamano / 1048576, 2) . ' MB';
    }

    public function scopeEnRevision(Builder $query): Builder
    {
        return $query->where('estado', 'en_revision');
    }

    public function scopeAprobados(Builder $query): Builder
    {
        return $query->where('estado', 'aprobado');
    }

    public function scopeRechazados(Builder $query): Builder
    {
        return $query->where('estado', 'rechazado');
    }
}
