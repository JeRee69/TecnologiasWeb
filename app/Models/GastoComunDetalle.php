<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GastoComunDetalle extends Model
{
    protected $table = 'gasto_comun_detalles';

    protected $fillable = ['concepto', 'monto'];

    protected $casts = ['monto' => 'integer'];

    public function gastoComun(): BelongsTo
    {
        return $this->belongsTo(GastoComun::class);
    }
}
