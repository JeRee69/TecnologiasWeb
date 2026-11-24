<?php

namespace Database\Seeders;

use App\Models\Departamento;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DuenoSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasColumn('departamentos', 'propietario_id')) {
            return;
        }

        $rol = Rol::where('nombre', 'Dueño')->first();
        if (! $rol) {
            return;
        }

        $dueno = User::updateOrCreate(
            ['email' => 'dueno@ediconnect.cl'],
            [
                'nombre' => 'Pedro Dueño',
                'rut' => '55.555.555-5',
                'telefono' => '+56955555555',
                'password' => Hash::make('Password123!'),
                'rol_id' => $rol->id,
                'estado' => 'activo',
            ]
        );

        Departamento::where('numero', '202')->update(['propietario_id' => $dueno->id]);
    }
}
