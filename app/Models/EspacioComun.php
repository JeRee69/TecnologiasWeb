<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EspacioComun extends Model
{
    protected $table = 'espacios_comunes';

    protected $fillable = ['nombre', 'descripcion', 'estado'];

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', 'activo');
    }
}
