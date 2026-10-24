<?php

namespace Database\Seeders;

use App\Models\Departamento;
use App\Models\GastoComun;
use Illuminate\Database\Seeder;

class GastoComunSeeder extends Seeder
{
    public function run(): void
    {
        $departamento202 = Departamento::where('numero', '202')->first();
        if (! $departamento202) {
            $departamento202 = Departamento::create([
                'numero' => '202',
                'piso' => 5,
                'estado' => 'activo',
            ]);
        }

        $gastos = [
            [
                'departamento_id' => $departamento202->id,
                'periodo' => '2026-10',
                'monto_total' => 68500,
                'desglose_json' => [
                    'consumos_basicos' => 28500,
                    'mantenimiento' => 25000,
                    'fondo_reserva' => 15000,
                ],
                'fecha_emision' => '2026-10-01',
                'fecha_vencimiento' => '2026-10-31',
                'estado' => 'Pendiente',
                'notas' => 'Gasto común mensual de octubre.',
            ],
            [
                'departamento_id' => $departamento202->id,
                'periodo' => '2026-09',
                'monto_total' => 64000,
                'desglose_json' => [
                    'consumos_basicos' => 26000,
                    'mantenimiento' => 24000,
                    'fondo_reserva' => 14000,
                ],
                'fecha_emision' => '2026-09-01',
                'fecha_vencimiento' => '2026-09-30',
                'estado' => 'Pagado',
                'notas' => 'Gasto común mensual de septiembre.',
            ],
        ];

        foreach ($gastos as $gasto) {
            GastoComun::updateOrCreate(
                [
                    'departamento_id' => $gasto['departamento_id'],
                    'periodo' => $gasto['periodo'],
                ],
                $gasto
            );
        }
    }
}
