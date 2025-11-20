<?php

namespace Database\Seeders;

use App\Models\Warehouse\WarehouseLocationStyle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WarehouseLocationStyleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1-cara style (solo front)
        WarehouseLocationStyle::create([
            'uid' => Str::uuid(),
            'code' => 'STY-1CARA-FRONT',
            'name' => 'Estilo 1 Cara Frontal',
            'faces' => ['front'],
            'default_levels' => 5,
            'default_sections' => 4,
            'description' => 'Estantería de una sola cara accesible desde el frente',
            'available' => true,
        ]);

        // 2-cara style (isla de 2 caras: front-back)
        WarehouseLocationStyle::create([
            'uid' => Str::uuid(),
            'code' => 'STY-ISLA-2CARAS',
            'name' => 'Isla 2 Caras (Front-Back)',
            'faces' => ['front', 'back'],
            'default_levels' => 5,
            'default_sections' => 3,
            'description' => 'Isla accesible desde frente y parte posterior',
            'available' => true,
        ]);
    }
}
