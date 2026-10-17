<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactoEmergencia extends Model
{
    use HasFactory;

    protected $table = 'contactos_emergencia';

    protected $fillable = [
        'departamento_id',
        'user_id',
        'nombre',
        'telefono',
        'relacion',
    ];

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class, 'departamento_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function residente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
