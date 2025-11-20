<?php

namespace Database\Seeders;

use App\Models\Warehouse\WarehouseLocationStyle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StandStyleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Crea 3 estilos de estanterías
     */
    public function run(): void
    {
        $styles = [
            [
                'code' => 'ROW',
                'name' => 'Pasillo Lineal',
                'description' => 'Estantería de pasillo tradicional con acceso desde ambos lados',
                'faces' => ['left', 'right'],
                'default_levels' => 4,
                'default_sections' => 6,
            ],
            [
                'code' => 'ISLAND',
                'name' => 'Isla Central',
                'description' => 'Estantería isla con acceso desde los 4 lados (360°)',
                'faces' => ['left', 'right', 'front', 'back'],
                'default_levels' => 3,
                'default_sections' => 5,
            ],
            [
                'code' => 'WALL',
                'name' => 'Estantería de Pared',
                'description' => 'Estantería adosada a la pared con acceso frontal',
                'faces' => ['front'],
                'default_levels' => 5,
                'default_sections' => 8,
            ],
        ];

        foreach ($styles as $style) {
            WarehouseLocationStyle::create([
                'uid' => Str::uuid(),
                'code' => $style['code'],
                'name' => $style['name'],
                'description' => $style['description'],
                'faces' => $style['faces'],
                'default_levels' => $style['default_levels'],
                'default_sections' => $style['default_sections'],
                'available' => true,
            ]);
        }

        $this->command->info('✅ 3 estilos de estanterías creados exitosamente');
    }
}
