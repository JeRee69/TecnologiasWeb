<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EspacioComun extends Model
{
    protected $table = 'espacios_comunes';

    protected $fillable = ['nombre', 'descripcion', 'estado', 'aforo', 'hora_apertura', 'hora_cierre', 'costo', 'reglas'];

    protected $casts = ['aforo' => 'integer', 'costo' => 'decimal:0'];

    public function tieneConfiguracion(): bool
    {
        return $this->aforo !== null && $this->hora_apertura !== null && $this->hora_cierre !== null
            && $this->costo !== null && $this->reglas !== null;
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', 'activo');
    }
}
