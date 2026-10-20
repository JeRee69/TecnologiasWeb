<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Encomienda extends Model
{
    use HasFactory;

    protected $table = 'encomiendas';

    protected $fillable = [
        'departamento_id',
        'conserje_id',
        'empresa_delivery',
        'fecha_ingreso',
        'fecha_retiro',
        'estado',
        'codigo_seguimiento',
        'descripcion',
    ];

    protected $casts = [
        'fecha_ingreso' => 'datetime',
        'fecha_retiro' => 'datetime',
    ];

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class, 'departamento_id');
    }

    public function conserje(): BelongsTo
    {
        return $this->belongsTo(User::class, 'conserje_id');
    }

    public function scopeIngresadas(Builder $query): Builder
    {
        return $query->where('estado', 'Ingresado');
    }

    public function scopePendientes(Builder $query): Builder
    {
        return $query->whereIn('estado', ['Ingresado', 'Notificado', 'En Custodia']);
    }

    public function scopeMayorPermanencia(Builder $query): Builder
    {
        return $query->orderByRaw("CASE WHEN estado = 'Retirado' THEN 1 ELSE 0 END")
            ->orderBy('fecha_ingreso', 'asc')
            ->orderBy('id', 'asc');
    }

    public function diasPermanencia(): int
    {
        $fin = $this->fecha_retiro ?? now();
        return (int) $this->fecha_ingreso->diffInDays($fin);
    }

    public function horasPermanencia(): int
    {
        $fin = $this->fecha_retiro ?? now();
        return (int) $this->fecha_ingreso->diffInHours($fin);
    }

    public function tiempoTranscurrido(): string
    {
        $fin = $this->fecha_retiro ?? now();
        $dias = (int) $this->fecha_ingreso->diffInDays($fin);
        $horas = (int) $this->fecha_ingreso->copy()->addDays($dias)->diffInHours($fin);

        if ($dias > 0) {
            return $horas > 0 ? "{$dias}d {$horas}h" : "{$dias} d";
        }

        if ($horas > 0) {
            return "{$horas} h";
        }

        $minutos = (int) $this->fecha_ingreso->diffInMinutes($fin);
        return $minutos > 0 ? "{$minutos} min" : "Recién recibido";
    }

    public function tienePermanenciaProlongada(int $umbralDias = 3): bool
    {
        if ($this->estado === 'Retirado') {
            return false;
        }

        return $this->diasPermanencia() >= $umbralDias;
    }
}
