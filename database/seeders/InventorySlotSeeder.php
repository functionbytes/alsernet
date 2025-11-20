<?php

namespace Database\Seeders;

use App\Models\Warehouse\WarehouseInventorySlot;
use App\Models\Warehouse\WarehouseLocation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InventorySlotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Crea todas las posiciones (slots) para cada estantería
     * Total de slots = suma de (caras × niveles × secciones) para cada stand
     */
    public function run(): void
    {
        $stands = WarehouseLocation::all();
        $slotCount = 0;
        $barcodeCounter = 1000;

        foreach ($stands as $stand) {
            $facesCount = count($stand->style?->faces ?? []);

            foreach ($stand->style?->faces ?? [] as $face) {
                for ($level = 1; $level <= $stand->total_levels; $level++) {
                    for ($section = 1; $section <= $stand->total_sections; $section++) {
                        WarehouseInventorySlot::create([
                            'uid' => Str::uuid(),
                            'stand_id' => $stand->id,
                            'product_id' => null, // Sin producto inicialmente
                            'face' => $face,
                            'level' => $level,
                            'section' => $section,
                            'barcode' => 'SLOT-' . str_pad($barcodeCounter++, 6, '0', STR_PAD_LEFT),
                            'quantity' => 0,
                            'max_quantity' => null, // Sin límite inicialmente
                            'weight_current' => 0,
                            'weight_max' => null,
                            'is_occupied' => false,
                        ]);

                        $slotCount++;
                    }
                }
            }
        }

        $this->command->info("✅ {$slotCount} posiciones de inventario creadas exitosamente");
        $this->command->line("   • Código de barras: SLOT-001000 hasta SLOT-{$barcodeCounter}");
    }
}
