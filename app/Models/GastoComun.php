<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GastoComun extends Model
{
    protected $table = 'gastos_comunes';

    protected $fillable = ['departamento_id', 'periodo', 'monto', 'estado'];

    protected $casts = ['periodo' => 'date', 'monto' => 'integer'];

    public function detalles(): HasMany
    {
        return $this->hasMany(GastoComunDetalle::class)->orderBy('id');
    }

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class);
    }
}
