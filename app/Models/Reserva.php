<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\CarbonImmutable;

class Reserva extends Model
{
    protected $fillable = ['espacio_comun_id', 'user_id', 'fecha', 'hora_inicio', 'hora_fin', 'estado'];

    protected $casts = ['fecha' => 'date', 'cancelada_at' => 'datetime'];

    public const ESTADOS = ['pendiente' => 'Pendiente', 'confirmada' => 'Confirmada', 'rechazada' => 'Rechazada', 'cancelada' => 'Cancelada'];

    public function eventos(): HasMany
    {
        return $this->hasMany(ReservaEvento::class)->orderByDesc('id');
    }

    public function puedeCancelarse(): bool
    {
        return in_array($this->estado, ['pendiente', 'confirmada'], true)
            && CarbonImmutable::parse($this->fecha->toDateString().' '.$this->hora_inicio, config('app.timezone'))
                ->isAfter(CarbonImmutable::now(config('app.timezone')));
    }

    public function espacioComun(): BelongsTo
    {
        return $this->belongsTo(EspacioComun::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeVigentes(Builder $query): Builder
    {
        return $query->whereIn('estado', ['pendiente', 'confirmada']);
    }
}
