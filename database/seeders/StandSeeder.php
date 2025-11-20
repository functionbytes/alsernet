<?php

namespace Database\Seeders;

use App\Models\Warehouse\WarehouseFloor;
use App\Models\Warehouse\WarehouseLocation;
use App\Models\Warehouse\WarehouseLocationStyle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Crea múltiples estanterías distribuidas en los pisos
     */
    public function run(): void
    {
        $floors = WarehouseFloor::all();
        $styles = WarehouseLocationStyle::all();

        $rowStyle = $styles->where('code', 'ROW')->first();
        $islandStyle = $styles->where('code', 'ISLAND')->first();
        $wallStyle = $styles->where('code', 'WALL')->first();

        $standCount = 0;

        // PLANTA 1: Pasillos lineales y una isla central
        $floor1 = $floors->where('code', 'P1')->first();
        if ($floor1 && $rowStyle) {
            for ($i = 1; $i <= 5; $i++) {
                WarehouseLocation::create([
                    'uid' => Str::uuid(),
                    'floor_id' => $floor1->id,
                    'stand_style_id' => $rowStyle->id,
                    'code' => "PASILLO1{$i}A",
                    'barcode' => "BAR-P1-{$i}A",
                    'position_x' => $i * 3,
                    'position_y' => 2,
                    'position_z' => 0,
                    'total_levels' => 4,
                    'total_sections' => 6,
                    'capacity' => 500.00, // kg
                    'available' => true,
                ]);
                $standCount++;
            }

            // Isla central en planta 1
            if ($islandStyle) {
                WarehouseLocation::create([
                    'uid' => Str::uuid(),
                    'floor_id' => $floor1->id,
                    'stand_style_id' => $islandStyle->id,
                    'code' => 'ISLA01',
                    'barcode' => 'BAR-ISLA01',
                    'position_x' => 8,
                    'position_y' => 5,
                    'position_z' => 0,
                    'total_levels' => 3,
                    'total_sections' => 5,
                    'capacity' => 800.00,
                    'available' => true,
                    'notes' => 'Isla de circulación rápida',
                ]);
                $standCount++;
            }
        }

        // PLANTA 2: Más pasillos y estanterías de pared
        $floor2 = $floors->where('code', 'P2')->first();
        if ($floor2 && $rowStyle) {
            for ($i = 1; $i <= 4; $i++) {
                WarehouseLocation::create([
                    'uid' => Str::uuid(),
                    'floor_id' => $floor2->id,
                    'stand_style_id' => $rowStyle->id,
                    'code' => "PASILLO2{$i}A",
                    'barcode' => "BAR-P2-{$i}A",
                    'position_x' => $i * 3,
                    'position_y' => 2,
                    'position_z' => 1,
                    'total_levels' => 4,
                    'total_sections' => 6,
                    'capacity' => 450.00,
                    'available' => true,
                ]);
                $standCount++;
            }

            // Pared lateral en planta 2
            if ($wallStyle) {
                WarehouseLocation::create([
                    'uid' => Str::uuid(),
                    'floor_id' => $floor2->id,
                    'stand_style_id' => $wallStyle->id,
                    'code' => 'PARED-P2-A',
                    'barcode' => 'BAR-PARED-P2-A',
                    'position_x' => 0,
                    'position_y' => 5,
                    'position_z' => 1,
                    'total_levels' => 5,
                    'total_sections' => 8,
                    'capacity' => 1000.00,
                    'available' => true,
                    'notes' => 'Estantería de pared con productos de bajo movimiento',
                ]);
                $standCount++;
            }
        }

        // PLANTA 3: Pasillos largos
        $floor3 = $floors->where('code', 'P3')->first();
        if ($floor3 && $rowStyle) {
            for ($i = 1; $i <= 3; $i++) {
                WarehouseLocation::create([
                    'uid' => Str::uuid(),
                    'floor_id' => $floor3->id,
                    'stand_style_id' => $rowStyle->id,
                    'code' => "PASILLO3{$i}A",
                    'barcode' => "BAR-P3-{$i}A",
                    'position_x' => $i * 3,
                    'position_y' => 2,
                    'position_z' => 2,
                    'total_levels' => 4,
                    'total_sections' => 6,
                    'capacity' => 400.00,
                    'available' => true,
                    'notes' => 'Planta de almacenamiento de largo plazo',
                ]);
                $standCount++;
            }
        }

        // SÓTANO: Estanterías especializadas
        $floorsotano = $floors->where('code', 'S0')->first();
        if ($floorsotano && $islandStyle) {
            WarehouseLocation::create([
                'uid' => Str::uuid(),
                'floor_id' => $floorsotano->id,
                'stand_style_id' => $islandStyle->id,
                'code' => 'REFRIG01',
                'barcode' => 'BAR-REFRIG01',
                'position_x' => 5,
                'position_y' => 5,
                'position_z' => -1,
                'total_levels' => 3,
                'total_sections' => 4,
                'capacity' => 300.00,
                'available' => true,
                'notes' => 'Zona refrigerada a -5°C',
            ]);
            $standCount++;
        }

        $this->command->info("✅ {$standCount} estanterías creadas exitosamente");
    }
}
