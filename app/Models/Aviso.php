<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Aviso extends Model
{
    use HasFactory;

    protected $table = 'avisos';

    protected $fillable = [
        'autor_id',
        'titulo',
        'contenido',
        'prioridad',
        'estado',
        'fecha_publicacion',
    ];

    protected $casts = [
        'fecha_publicacion' => 'datetime',
    ];

    public const PRIORIDADES = [
        'Baja' => 'Baja',
        'Media' => 'Media',
        'Alta' => 'Alta',
        'Urgente' => 'Urgente',
    ];

    public const ESTADOS = [
        'Publicado' => 'Publicado',
        'Retirado' => 'Retirado',
    ];

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autor_id');
    }

    public function scopeVigentes(Builder $query): Builder
    {
        return $query->where('estado', 'Publicado');
    }

    public function scopePrioridadOrdenada(Builder $query): Builder
    {
        return $query->orderByRaw("CASE prioridad WHEN 'Urgente' THEN 1 WHEN 'Alta' THEN 2 WHEN 'Media' THEN 3 WHEN 'Baja' THEN 4 ELSE 5 END")
            ->orderByDesc('fecha_publicacion')
            ->orderByDesc('id');
    }

    public function esVigente(): bool
    {
        return $this->estado === 'Publicado';
    }
}
