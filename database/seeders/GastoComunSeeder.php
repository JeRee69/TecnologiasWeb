<?php

namespace Database\Seeders;

use App\Models\Departamento;
use App\Models\GastoComun;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class GastoComunSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('departamentos') || ! Schema::hasTable('gastos_comunes') || ! Schema::hasTable('gasto_comun_detalles')) {
            return;
        }

        $departamento202 = Departamento::firstOrCreate(
            ['numero' => '202'],
            ['piso' => 5, 'estado' => 'activo']
        );

        $residente = User::where('email', 'residente@ediconnect.cl')->first();
        if ($residente && ! $departamento202->residentes()->where('users.id', $residente->id)->exists()) {
            $departamento202->residentes()->attach($residente->id, ['es_titular' => true]);
        }

        $cobros = [
            [
                'periodo' => '2026-10-01',
                'monto' => 68500,
                'fecha_vencimiento' => '2026-10-31',
                'estado' => 'pendiente',
                'detalles' => [
                    ['concepto' => 'Consumos básicos', 'monto' => 28500],
                    ['concepto' => 'Mantenimiento de espacios comunes', 'monto' => 25000],
                    ['concepto' => 'Fondo de reserva', 'monto' => 15000],
                ],
            ],
            [
                'periodo' => '2026-09-01',
                'monto' => 64000,
                'fecha_vencimiento' => '2026-09-30',
                'estado' => 'pagado',
                'detalles' => [
                    ['concepto' => 'Consumos básicos', 'monto' => 26000],
                    ['concepto' => 'Mantenimiento de espacios comunes', 'monto' => 24000],
                    ['concepto' => 'Fondo de reserva', 'monto' => 14000],
                ],
            ],
        ];

        foreach ($cobros as $item) {
            $gasto = GastoComun::updateOrCreate(
                [
                    'departamento_id' => $departamento202->id,
                    'periodo' => $item['periodo'],
                ],
                [
                    'monto' => $item['monto'],
                    'fecha_vencimiento' => $item['fecha_vencimiento'],
                    'estado' => $item['estado'],
                ]
            );

            if ($gasto->detalles()->count() === 0) {
                $gasto->detalles()->createMany($item['detalles']);
            }
        }
    }
}
