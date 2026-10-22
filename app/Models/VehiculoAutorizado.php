<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehiculoAutorizado extends Model
{
    use HasFactory;

    protected $table = 'vehiculos_autorizados';

    protected $fillable = [
        'departamento_id',
        'conserje_id',
        'patente',
        'marca',
        'modelo',
        'color',
        'tipo',
        'estado',
        'vigente_hasta',
        'observaciones',
    ];

    protected $casts = [
        'vigente_hasta' => 'date',
    ];

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class, 'departamento_id');
    }

    public function conserje(): BelongsTo
    {
        return $this->belongsTo(User::class, 'conserje_id');
    }

    public function scopeAutorizados(Builder $query): Builder
    {
        return $query->where('estado', 'Autorizado');
    }

    public function scopeRevocados(Builder $query): Builder
    {
        return $query->where('estado', 'Revocado');
    }

    public function scopePorPatente(Builder $query, string $patente): Builder
    {
        $limpia = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $patente));
        return $query->where('patente', $limpia);
    }

    public function estaAutorizado(): bool
    {
        if ($this->estado !== 'Autorizado') {
            return false;
        }

        if ($this->vigente_hasta && $this->vigente_hasta->isPast() && ! $this->vigente_hasta->isToday()) {
            return false;
        }

        return true;
    }
}
