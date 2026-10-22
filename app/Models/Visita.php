<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Visita extends Model
{
    use HasFactory;

    protected $table = 'visitas';

    protected $fillable = [
        'departamento_id',
        'conserje_id',
        'nombre_visitante',
        'rut_visitante',
        'fecha_ingreso',
        'fecha_salida',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_ingreso' => 'datetime',
        'fecha_salida' => 'datetime',
    ];

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class, 'departamento_id');
    }

    public function conserje(): BelongsTo
    {
        return $this->belongsTo(User::class, 'conserje_id');
    }

    public function scopeEnRecinto(Builder $query): Builder
    {
        return $query->where('estado', 'En Recinto');
    }

    public function scopeFinalizadas(Builder $query): Builder
    {
        return $query->where('estado', 'Finalizada');
    }

    public function estaEnRecinto(): bool
    {
        return $this->estado === 'En Recinto';
    }
}
