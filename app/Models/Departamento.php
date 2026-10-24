<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Departamento extends Model
{
    use HasFactory;

    protected $table = 'departamentos';

    protected $fillable = [
        'numero',
        'piso',
        'estado',
    ];

    protected $casts = [
        'piso' => 'integer',
    ];

    public function gastosComunes(): HasMany
    {
        return $this->hasMany(GastoComun::class);
    }

    public function residentes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'departamento_user')
            ->withPivot('es_titular')
            ->withTimestamps();
    }

    public function contactosEmergencia(): HasMany
    {
        return $this->hasMany(ContactoEmergencia::class, 'departamento_id');
    }

    public function encomiendas(): HasMany
    {
        return $this->hasMany(Encomienda::class, 'departamento_id');
    }

    public function visitas(): HasMany
    {
        return $this->hasMany(Visita::class, 'departamento_id');
    }

    public function vehiculosAutorizados(): HasMany
    {
        return $this->hasMany(VehiculoAutorizado::class, 'departamento_id');
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', 'activo');
    }

    public function estaActivo(): bool
    {
        return $this->estado === 'activo';
    }
}
