<?php

namespace Database\Seeders;

use App\Models\Warehouse\WarehouseFloor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FloorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Crea 4 pisos/plantas para el almacén
     */
    public function run(): void
    {
        $floors = [
            [
                'code' => 'P1',
                'name' => 'Planta 1',
                'description' => 'Planta principal del almacén',
                'order' => 1,
            ],
            [
                'code' => 'P2',
                'name' => 'Planta 2',
                'description' => 'Segunda planta de almacenamiento',
                'order' => 2,
            ],
            [
                'code' => 'P3',
                'name' => 'Planta 3',
                'description' => 'Tercera planta para productos de poco movimiento',
                'order' => 3,
            ],
            [
                'code' => 'S0',
                'name' => 'Sótano',
                'description' => 'Sótano para productos refrigerados y especiales',
                'order' => 0,
            ],
        ];

        foreach ($floors as $floor) {
            WarehouseFloor::create([
                'uid' => Str::uuid(),
                'code' => $floor['code'],
                'name' => $floor['name'],
                'description' => $floor['description'],
                'order' => $floor['order'],
                'available' => true,
            ]);
        }

        $this->command->info('✅ 4 pisos creados exitosamente');
    }
}
