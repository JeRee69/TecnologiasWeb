<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Incidencia extends Model
{
    use HasFactory;

    protected $table = 'incidencias';

    protected $fillable = [
        'user_id',
        'departamento_id',
        'categoria',
        'titulo',
        'descripcion',
        'estado',
        'personal_resolucion_id',
        'respuesta_administracion',
        'fecha_resolucion',
    ];

    protected $casts = [
        'fecha_resolucion' => 'datetime',
    ];

    public const CATEGORIAS = [
        'Espacios Comunes',
        'Ascensores',
        'Seguridad',
        'Limpieza',
        'Ruidos Molestos',
        'Otro',
    ];

    public const ESTADOS = [
        'Pendiente' => 'Pendiente',
        'En Proceso' => 'En Proceso',
        'Resuelta' => 'Resuelta',
        'Rechazada' => 'Rechazada',
    ];

    public function residente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class, 'departamento_id');
    }

    public function personalResolucion(): BelongsTo
    {
        return $this->belongsTo(User::class, 'personal_resolucion_id');
    }

    public function scopeDeUsuario(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopePorEstado(Builder $query, string $estado): Builder
    {
        return $query->where('estado', $estado);
    }

    public function scopePorCategoria(Builder $query, string $categoria): Builder
    {
        return $query->where('categoria', $categoria);
    }
}
