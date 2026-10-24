<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GastoComun extends Model
{
    use HasFactory;

    protected $table = 'gastos_comunes';

    protected $fillable = [
        'departamento_id',
        'periodo',
        'monto_total',
        'desglose_json',
        'fecha_emision',
        'fecha_vencimiento',
        'estado',
        'notas',
    ];

    protected $casts = [
        'desglose_json' => 'array',
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
        'monto_total' => 'integer',
    ];

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class, 'departamento_id');
    }

    public function comprobantes(): HasMany
    {
        return $this->hasMany(ComprobantePago::class, 'gasto_comun_id');
    }

    public function ultimoComprobante(): HasOne
    {
        return $this->hasOne(ComprobantePago::class, 'gasto_comun_id')->latestOfMany();
    }

    public function comprobanteEnRevision(): HasOne
    {
        return $this->hasOne(ComprobantePago::class, 'gasto_comun_id')
            ->where('estado', 'en_revision')
            ->latestOfMany();
    }

    public function montoFormateado(): string
    {
        return '$ ' . number_format($this->monto_total, 0, ',', '.');
    }

    public function permiteCargarComprobante(): bool
    {
        return in_array($this->estado, ['Pendiente', 'Moroso', 'En revisión']);
    }

    public function scopePendientes(Builder $query): Builder
    {
        return $query->whereIn('estado', ['Pendiente', 'Moroso', 'En revisión']);
    }

    public function scopePagados(Builder $query): Builder
    {
        return $query->where('estado', 'Pagado');
    }
}
