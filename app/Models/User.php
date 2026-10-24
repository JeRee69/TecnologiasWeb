<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'nombre',
        'email',
        'rut',
        'telefono',
        'email_secundario',
        'password',
        'rol_id',
        'estado',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function departamentos(): BelongsToMany
    {
        return $this->belongsToMany(Departamento::class, 'departamento_user')
            ->withPivot('es_titular')
            ->withTimestamps();
    }

    public function visitasRegistradas(): HasMany
    {
        return $this->hasMany(Visita::class, 'conserje_id');
    }

    public function vehiculosRegistrados(): HasMany
    {
        return $this->hasMany(VehiculoAutorizado::class, 'conserje_id');
    }

    public function avisosCreados(): HasMany
    {
        return $this->hasMany(Aviso::class, 'autor_id');
    }

    public function incidencias(): HasMany
    {
        return $this->hasMany(Incidencia::class, 'user_id');
    }

    public function comprobantesPago(): HasMany
    {
        return $this->hasMany(ComprobantePago::class, 'user_id');
    }

    public function comprobantesRevisados(): HasMany
    {
        return $this->hasMany(ComprobantePago::class, 'revisado_por');
    }

    public function tieneRol(string $nombreRol): bool
    {
        return strtolower($this->rol?->nombre ?? '') === strtolower($nombreRol);
    }

    public function esAdministrador(): bool
    {
        return $this->tieneRol('Administrador');
    }

    public function esConserje(): bool
    {
        return $this->tieneRol('Conserje');
    }

    public function esResidente(): bool
    {
        return $this->tieneRol('Residente');
    }

    public function estaActivo(): bool
    {
        return $this->estado === 'activo';
    }
}
