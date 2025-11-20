<?php

namespace Database\Seeders;

use App\Models\Warehouse\Warehouse;
use App\Models\Warehouse\WarehouseFloor;
use App\Models\Warehouse\WarehouseLocation;
use App\Models\Warehouse\WarehouseLocationStyle;
use App\Models\Warehouse\WarehouseLocationSection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WarehouseExampleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener estilos
        $style1Cara = WarehouseLocationStyle::where('code', 'STY-1CARA-FRONT')->first();
        $styleIsla2Caras = WarehouseLocationStyle::where('code', 'STY-ISLA-2CARAS')->first();

        // ==========================================
        // WAREHOUSE: MADRID
        // ==========================================
        $warehouseMadrid = Warehouse::create([
            'uid' => Str::uuid(),
            'name' => 'Almacén Madrid',
            'code' => 'MDRDD01',
            'description' => 'Almacén de Madrid - Polígono Industrial Madrid',
            'available' => true,
        ]);

        // Floor 1 - Madrid
        $floorMadrid1 = WarehouseFloor::create([
            'uid' => Str::uuid(),
            'warehouse_id' => $warehouseMadrid->id,
            'code' => 'P01',
            'name' => 'Planta Baja',
            'level' => 1,
            'available' => true,
        ]);

        // ==========================================
        // LOCATION 1: 1-Cara Frontal (Madrid)
        // ==========================================
        $location1CaraMadrid = WarehouseLocation::create([
            'uid' => Str::uuid(),
            'warehouse_id' => $warehouseMadrid->id,
            'floor_id' => $floorMadrid1->id,
            'code' => 'UB-001',
            'style_id' => $style1Cara->id,
            'position_x' => 1.5,
            'position_y' => 2.0,
            'total_levels' => 3,
            'available' => true,
            'notes' => 'Ubicación de una sola cara frontal en planta baja',
        ]);

        // Sections for 1-Cara location
        for ($i = 1; $i <= 3; $i++) {
            WarehouseLocationSection::create([
                'uid' => Str::uuid(),
                'location_id' => $location1CaraMadrid->id,
                'code' => "SEC-{$i}",
                'barcode' => "BARSEC-M001-{$i}",
                'level' => $i,
                'face' => null, // 1-cara no tiene face específica
                'max_quantity' => 100,
                'available' => true,
            ]);
        }

        // ==========================================
        // LOCATION 2: Isla 2-Caras (Madrid)
        // ==========================================
        $locationIsla2CarasMadrid = WarehouseLocation::create([
            'uid' => Str::uuid(),
            'warehouse_id' => $warehouseMadrid->id,
            'floor_id' => $floorMadrid1->id,
            'code' => 'ISLA-M01',
            'style_id' => $styleIsla2Caras->id,
            'position_x' => 4.5,
            'position_y' => 2.0,
            'total_levels' => 3,
            'available' => true,
            'notes' => 'Isla de 2 caras (frente-atrás) en planta baja Madrid',
        ]);

        // Sections for Isla 2-Caras (front-back)
        for ($i = 1; $i <= 3; $i++) {
            // Front face
            WarehouseLocationSection::create([
                'uid' => Str::uuid(),
                'location_id' => $locationIsla2CarasMadrid->id,
                'code' => "SEC-{$i}-F",
                'barcode' => "BARSEC-M02F-{$i}",
                'level' => $i,
                'face' => 'front',
                'max_quantity' => 120,
                'available' => true,
            ]);

            // Back face
            WarehouseLocationSection::create([
                'uid' => Str::uuid(),
                'location_id' => $locationIsla2CarasMadrid->id,
                'code' => "SEC-{$i}-B",
                'barcode' => "BARSEC-M02B-{$i}",
                'level' => $i,
                'face' => 'back',
                'max_quantity' => 120,
                'available' => true,
            ]);
        }

        // ==========================================
        // WAREHOUSE: CORUÑA (Con 3 pisos)
        // ==========================================
        $warehouseCoruna = Warehouse::create([
            'uid' => Str::uuid(),
            'name' => 'Almacén Coruña',
            'code' => 'COR001',
            'description' => 'Almacén de Coruña - Polígono Industrial Coruña',
            'available' => true,
        ]);

        // Floor 1 - Coruña
        $floorCoruna1 = WarehouseFloor::create([
            'uid' => Str::uuid(),
            'warehouse_id' => $warehouseCoruna->id,
            'code' => 'P01',
            'name' => 'Planta Baja',
            'level' => 1,
            'available' => true,
        ]);

        // Floor 2 - Coruña
        $floorCoruna2 = WarehouseFloor::create([
            'uid' => Str::uuid(),
            'warehouse_id' => $warehouseCoruna->id,
            'code' => 'P02',
            'name' => 'Primera Planta',
            'level' => 2,
            'available' => true,
        ]);

        // Floor 3 - Coruña
        $floorCoruna3 = WarehouseFloor::create([
            'uid' => Str::uuid(),
            'warehouse_id' => $warehouseCoruna->id,
            'code' => 'P03',
            'name' => 'Segunda Planta',
            'level' => 3,
            'available' => true,
        ]);

        // ==========================================
        // LOCATION 3: 1-Cara (Planta 1 Coruña)
        // ==========================================
        $location1CaraCoruna1 = WarehouseLocation::create([
            'uid' => Str::uuid(),
            'warehouse_id' => $warehouseCoruna->id,
            'floor_id' => $floorCoruna1->id,
            'code' => 'UB-101',
            'style_id' => $style1Cara->id,
            'position_x' => 2.0,
            'position_y' => 3.0,
            'position_z' => 0,
            'capacity' => 900,
            'available' => true,
            'notes' => 'Ubicación de 1 cara frontal en planta baja Coruña',
        ]);

        // Sections for 1-Cara location
        for ($i = 1; $i <= 3; $i++) {
            WarehouseLocationSection::create([
                'uid' => Str::uuid(),
                'location_id' => $location1CaraCoruna1->id,
                'code' => "SEC-{$i}",
                'barcode' => "BARSEC-C101-{$i}",
                'level' => $i,
                'face' => null,
                'max_quantity' => 80,
                'available' => true,
            ]);
        }

        // ==========================================
        // LOCATION 4: Isla 2-Caras (Planta 2 Coruña)
        // ==========================================
        $locationIsla2CarasCoruna = WarehouseLocation::create([
            'uid' => Str::uuid(),
            'warehouse_id' => $warehouseCoruna->id,
            'floor_id' => $floorCoruna2->id,
            'code' => 'ISLA-C01',
            'style_id' => $styleIsla2Caras->id,
            'position_x' => 5.0,
            'position_y' => 5.0,
            'position_z' => 0,
            'capacity' => 1800,
            'available' => true,
            'notes' => 'Isla de 2 caras (frente-atrás) en primera planta Coruña',
        ]);

        // Sections for Isla 2-Caras (front-back)
        for ($i = 1; $i <= 3; $i++) {
            // Front face
            WarehouseLocationSection::create([
                'uid' => Str::uuid(),
                'location_id' => $locationIsla2CarasCoruna->id,
                'code' => "SEC-{$i}-F",
                'barcode' => "BARSEC-C01F-{$i}",
                'level' => $i,
                'face' => 'front',
                'max_quantity' => 150,
                'available' => true,
            ]);

            // Back face
            WarehouseLocationSection::create([
                'uid' => Str::uuid(),
                'location_id' => $locationIsla2CarasCoruna->id,
                'code' => "SEC-{$i}-B",
                'barcode' => "BARSEC-C01B-{$i}",
                'level' => $i,
                'face' => 'back',
                'max_quantity' => 150,
                'available' => true,
            ]);
        }

        // ==========================================
        // LOCATION 5: 1-Cara (Planta 3 Coruña)
        // ==========================================
        $location1CaraCoruna3 = WarehouseLocation::create([
            'uid' => Str::uuid(),
            'warehouse_id' => $warehouseCoruna->id,
            'floor_id' => $floorCoruna3->id,
            'code' => 'UB-301',
            'style_id' => $style1Cara->id,
            'position_x' => 3.0,
            'position_y' => 4.0,
            'position_z' => 0,
            'capacity' => 800,
            'available' => true,
            'notes' => 'Ubicación de 1 cara frontal en segunda planta Coruña',
        ]);

        // Sections for 1-Cara location
        for ($i = 1; $i <= 2; $i++) {
            WarehouseLocationSection::create([
                'uid' => Str::uuid(),
                'location_id' => $location1CaraCoruna3->id,
                'code' => "SEC-{$i}",
                'barcode' => "BARSEC-C301-{$i}",
                'level' => $i,
                'face' => null,
                'max_quantity' => 100,
                'available' => true,
            ]);
        }
    }
}
