<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'nombre',
        'email',
        'rut',
        'telefono',
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
