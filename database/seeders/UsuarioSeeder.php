<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = [
            [
                'nombre' => 'Administrador Principal',
                'email' => 'admin@ediconnect.cl',
                'rut' => '11.111.111-1',
                'telefono' => '+56911111111',
                'password' => Hash::make('Password123!'),
                'rol_id' => 1,
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Juan Conserje',
                'email' => 'conserje@ediconnect.cl',
                'rut' => '22.222.222-2',
                'telefono' => '+56922222222',
                'password' => Hash::make('Password123!'),
                'rol_id' => 2,
                'estado' => 'activo',
            ],
            [
                'nombre' => 'María Residente',
                'email' => 'residente@ediconnect.cl',
                'rut' => '33.333.333-3',
                'telefono' => '+56933333333',
                'password' => Hash::make('Password123!'),
                'rol_id' => 3,
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Usuario Desactivado',
                'email' => 'inactivo@ediconnect.cl',
                'rut' => '44.444.444-4',
                'telefono' => '+56944444444',
                'password' => Hash::make('Password123!'),
                'rol_id' => 3,
                'estado' => 'inactivo',
            ],
        ];

        foreach ($usuarios as $usuario) {
            User::updateOrCreate(['email' => $usuario['email']], $usuario);
        }
    }
}
