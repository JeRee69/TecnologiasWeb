<?php

namespace Database\Seeders;

use App\Models\Rol;
use Illuminate\Database\Seeder;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'id' => 1,
                'nombre' => 'Administrador',
                'descripcion' => 'Gestión completa de usuarios, departamentos, cobros y áreas comunes.',
            ],
            [
                'id' => 2,
                'nombre' => 'Conserje',
                'descripcion' => 'Control operativo de bitácora, encomiendas, visitas y accesos vehiculares.',
            ],
            [
                'id' => 3,
                'nombre' => 'Residente',
                'descripcion' => 'Consulta de gastos comunes, comprobantes, reservas y avisos comunitarios.',
            ],
        ];

        foreach ($roles as $rol) {
            Rol::updateOrCreate(['id' => $rol['id']], $rol);
        }
    }
}
