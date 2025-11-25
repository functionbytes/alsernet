<?php

namespace Database\Seeders;

use App\Models\Warehouse\Warehouse;
use App\Models\Warehouse\WarehouseFloor;
use App\Models\Warehouse\WarehouseLocation;
use App\Models\Warehouse\WarehouseLocationStyle;
use App\Models\Warehouse\WarehouseLocationSection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Coruna2LocationsSeeder extends Seeder
{
    /**
     * Lista completa de códigos de secciones
     */
    private array $sections = [
        'ALTO-BUNKER', 'BUNKER', 'CB-1-1-2', 'CB-1-1-3', 'CB-1-2-1', 'CB-1-2-2', 'CB-1-2-3',
        'ES-1-1-1', 'ES-1-2-1', 'ES-1-2-2', 'ES-1-3-1', 'ES-1-3-2', 'ES-1-4-1', 'ES-1-4-2',
        'ES-1-5-1', 'ES-1-5-2', 'ES-1-6-1', 'ES-1-6-2', 'ES-1-7-1', 'ES-1-8-1', 'ES-1-8-2',
        'PB-1-S-1', 'PB-1-1-1', 'PB-1-1-2', 'PB-1-1-3', 'PB-1-1-4', 'PB-1-2-1', 'PB-1-2-2',
        'PB-1-2-3', 'PB-1-2-4', 'PB-1-3-1', 'PB-1-3-2', 'PB-1-3-3', 'PB-1-3-4', 'REMOLQUES',
        'SALTO-BUNKER', 'SES-1-2-2', 'SPB-1-1-1', 'S0-02-1-1-2', 'S0-02-2-7-3', 'S0-03-1-3-3',
        'S0-03-1-5-3', 'S0-03-1-6-2', 'S0-03-2-1-3', 'S0-04-1-6-2', 'S0-04-1-7-3', 'S0-09-1-3-1',
        'S0-13-1-9-1', 'S1-10-1-1-1', 'S1-10-2-2-2', 'S1-11-2-1-2', 'CB-1-1-1',
        '0-01-1-1-1', '0-01-1-1-2', '0-01-1-1-3', '0-01-1-2-2', '0-01-1-2-3', '0-01-1-3-1',
        '0-01-1-3-2', '0-01-1-3-3', '0-01-1-4-1', '0-01-1-4-2', '0-01-1-4-3', '0-01-1-5-2',
        '0-01-1-5-3', '0-01-1-6-1', '0-01-1-6-2', '0-01-1-7-2', '0-01-1-7-3', '0-01-1-8-1',
        '0-01-1-8-2', '0-01-1-8-3', '0-01-2-1-2', '0-01-2-1-3', '0-01-2-2-1', '0-01-2-2-2',
        '0-01-2-2-3', '0-01-2-3-2', '0-01-2-3-3', '0-01-2-4-2', '0-01-2-4-3', '0-01-2-5-1',
        '0-01-2-5-2', '0-01-2-5-3', '0-01-2-6-2', '0-01-2-6-3', '0-01-2-7-3',
        '0-02-1-1-1', '0-02-1-1-2', '0-02-1-1-3', '0-02-1-2-1', '0-02-1-2-2', '0-02-1-2-3',
        '0-02-1-3-1', '0-02-1-3-2', '0-02-1-3-3', '0-02-1-4-2', '0-02-1-4-3', '0-02-1-5-1',
        '0-02-1-5-2', '0-02-1-5-3', '0-02-1-6-2', '0-02-1-6-3', '0-02-1-7-1', '0-02-1-7-2',
        '0-02-1-7-3', '0-02-2-1-1', '0-02-2-1-2', '0-02-2-1-3', '0-02-2-2-1', '0-02-2-2-2',
        '0-02-2-2-3', '0-02-2-3-1', '0-02-2-3-2', '0-02-2-3-3', '0-02-2-4-1', '0-02-2-4-2',
        '0-02-2-4-3', '0-02-2-5-2', '0-02-2-5-3', '0-02-2-6-2', '0-02-2-6-3', '0-02-2-7-2',
        '0-02-2-7-3',
        '0-03-1-1-1', '0-03-1-1-2', '0-03-1-1-3', '0-03-1-2-1', '0-03-1-2-2', '0-03-1-2-3',
        '0-03-1-3-1', '0-03-1-3-2', '0-03-1-3-3', '0-03-1-4-1', '0-03-1-4-2', '0-03-1-4-3',
        '0-03-1-5-2', '0-03-1-5-3', '0-03-1-6-1', '0-03-1-6-2', '0-03-1-6-3', '0-03-1-7-1',
        '0-03-1-7-2', '0-03-1-7-3', '0-03-2-1-1', '0-03-2-1-2', '0-03-2-1-3', '0-03-2-2-1',
        '0-03-2-2-2', '0-03-2-2-3', '0-03-2-3-2', '0-03-2-3-3', '0-03-2-4-1', '0-03-2-4-2',
        '0-03-2-4-3', '0-03-2-5-2', '0-03-2-5-3', '0-03-2-6-1', '0-03-2-6-2', '0-03-2-6-3',
        '0-03-2-7-1', '0-03-2-7-2', '0-03-2-7-3',
        '0-04-1-1-2', '0-04-1-1-3', '0-04-1-2-1', '0-04-1-2-2', '0-04-1-2-3', '0-04-1-3-1',
        '0-04-1-3-2', '0-04-1-3-3', '0-04-1-4-1', '0-04-1-4-2', '0-04-1-4-3', '0-04-1-5-1',
        '0-04-1-5-2', '0-04-1-5-3', '0-04-1-6-1', '0-04-1-6-2', '0-04-1-6-3', '0-04-1-7-1',
        '0-04-1-7-2', '0-04-1-7-3', '0-04-2-1-1', '0-04-2-2-1', '0-04-2-2-2', '0-04-2-2-3',
        '0-04-2-3-1', '0-04-2-3-2', '0-04-2-3-3', '0-04-2-4-1', '0-04-2-5-1',
        '0-05-1-1-4', '0-05-1-2-1', '0-05-1-3-1', '0-05-1-3-2', '0-05-1-4-1', '0-05-1-4-2',
        '0-05-2-1-1', '0-05-2-1-2', '0-05-2-1-3', '0-05-2-2-1', '0-05-2-2-2', '0-05-2-2-3',
        '0-05-2-3-1', '0-05-2-3-2', '0-05-2-3-3', '0-05-2-4-1', '0-05-2-4-2', '0-05-2-4-3',
        '0-06-1-1-2', '0-06-1-1-3', '0-06-1-2-1', '0-06-1-2-2', '0-06-1-2-3', '0-06-1-3-2',
        '0-06-1-4-1', '0-06-1-4-2', '0-06-1-4-3',
        '0-07-1-1-1', '0-07-1-1-2', '0-07-1-1-3', '0-07-1-1-4', '0-07-1-1-5', '0-07-1-2-1',
        '0-07-1-2-2', '0-07-1-2-3', '0-07-1-2-4', '0-07-1-2-5', '0-07-1-3-1', '0-07-1-3-2',
        '0-07-1-3-3', '0-07-1-3-4', '0-07-1-3-5', '0-07-1-4-1', '0-07-1-4-2', '0-07-1-4-3',
        '0-07-1-4-4', '0-07-1-4-5', '0-07-2-1-1', '0-07-2-1-2', '0-07-2-1-3', '0-07-2-2-1',
        '0-07-2-2-2', '0-07-2-2-3', '0-07-2-3-1', '0-07-2-3-2', '0-07-2-3-3', '0-07-2-4-1',
        '0-07-2-4-2', '0-07-2-4-3', '0-07-2-5-1', '0-07-2-5-3',
        '0-08-1-1-1', '0-08-1-1-2', '0-08-1-1-3', '0-08-1-1-4', '0-08-1-2-1', '0-08-1-2-2',
        '0-08-1-2-3', '0-08-1-2-4', '0-08-1-3-1', '0-08-1-3-2', '0-08-1-3-3', '0-08-1-3-4',
        '0-08-1-4-1', '0-08-1-4-2', '0-08-1-4-3', '0-08-1-4-4', '0-08-1-5-1', '0-08-1-5-2',
        '0-08-1-5-3', '0-08-1-5-4', '0-08-2-1-1', '0-08-2-1-2', '0-08-2-2-1', '0-08-2-2-2',
        '0-08-2-2-3', '0-08-2-3-1', '0-08-2-3-2', '0-08-2-3-3', '0-08-2-3-4',
        '0-09-1-1-1', '0-09-1-1-2', '0-09-1-2-1', '0-09-1-2-2', '0-09-1-2-3', '0-09-1-3-1',
        '0-09-1-3-2', '0-09-1-3-3', '0-09-2-1-1', '0-09-2-1-2', '0-09-2-1-3', '0-09-2-1-4',
        '0-09-2-2-1', '0-09-2-2-2', '0-09-2-2-3', '0-09-2-2-4', '0-09-2-3-1', '0-09-2-3-2',
        '0-09-2-3-4', '0-09-2-4-1', '0-09-2-4-2', '0-09-2-5-1', '0-09-2-5-2', '0-09-2-5-4',
        '0-10-1-1-1', '0-10-1-1-2', '0-10-1-1-3', '0-10-1-1-4', '0-10-1-2-1', '0-10-1-2-2',
        '0-10-1-2-3', '0-10-1-2-4', '0-10-1-3-1', '0-10-1-3-2', '0-10-1-3-3', '0-10-1-3-4',
        '0-10-1-4-1', '0-10-1-4-2', '0-10-1-4-3', '0-10-1-4-4', '0-10-1-5-1', '0-10-1-5-2',
        '0-10-1-5-3', '0-10-1-5-4', '0-10-2-1-1', '0-10-2-1-2', '0-10-2-1-3', '0-10-2-1-4',
        '0-10-2-2-1', '0-10-2-2-2', '0-10-2-2-3', '0-10-2-2-4', '0-10-2-3-1', '0-10-2-3-2',
        '0-10-2-3-3', '0-10-2-3-4', '0-10-2-4-1', '0-10-2-4-2', '0-10-2-4-3', '0-10-2-4-4',
        '0-10-2-5-1', '0-10-2-5-2', '0-10-2-5-3', '0-10-2-6-1', '0-10-2-6-2', '0-10-2-6-3',
        '0-11-1-1-1', '0-11-1-1-2', '0-11-1-1-3', '0-11-1-2-1', '0-11-1-2-2', '0-11-1-2-3',
        '0-11-1-2-4', '0-11-1-3-1', '0-11-1-3-2', '0-11-1-3-3', '0-11-1-4-1', '0-11-1-4-2',
        '0-11-1-4-3', '0-11-1-5-1', '0-11-1-5-2', '0-11-1-5-3', '0-11-1-5-4', '0-11-1-6-1',
        '0-11-1-6-2', '0-11-1-6-3', '0-11-1-6-4', '0-11-2-1-1', '0-11-2-1-2', '0-11-2-1-3',
        '0-11-2-1-4', '0-11-2-2-1', '0-11-2-2-3', '0-11-2-3-1', '0-11-2-3-2', '0-11-2-3-3',
        '0-11-2-4-1', '0-11-2-4-2', '0-11-2-4-3', '0-11-2-4-4', '0-11-2-5-1', '0-11-2-5-2',
        '0-11-2-5-3',
        '0-12-1-1-1', '0-12-1-1-2', '0-12-1-1-4', '0-12-1-2-1', '0-12-1-2-2', '0-12-1-2-3',
        '0-12-1-3-2', '0-12-1-3-3', '0-12-1-3-4', '0-12-1-4-1', '0-12-1-4-2', '0-12-1-4-3',
        '0-12-1-4-4', '0-12-1-5-1', '0-12-1-5-2', '0-12-1-5-3', '0-12-2-1-1',
        '0-13-1-1-1', '0-13-1-1-2', '0-13-1-2-1', '0-13-1-2-2', '0-13-1-2-3', '0-13-1-3-1',
        '0-13-1-4-1', '0-13-1-4-3', '0-13-1-5-1', '0-13-1-5-2', '0-13-1-6-1', '0-13-1-6-2',
        '0-13-1-6-3', '0-13-1-7-1', '0-13-1-7-2', '0-13-1-7-3', '0-13-1-7-4', '0-13-1-8-1',
        '0-13-1-8-2', '0-13-1-8-3', '0-13-1-8-4', '0-13-1-9-2', '0-13-1-9-3', '0-13-1-9-4',
        '1-CANAS-10', '1-CANAS-1', '1-CANAS-11', '1-CANAS-12', '1-CANAS-2', '1-CANAS-3',
        '1-CANAS-4', '1-CANAS-5', '1-CANAS-6', '1-CANAS-7', '1-CANAS-8', '1-CANAS-9',
        '1-FRONTALGOLF', '1-01-1-1-1', '1-01-1-1-2', '1-01-1-2-1', '1-01-1-2-2', '1-01-1-2-3',
        '1-01-1-3-1', '1-01-1-3-2', '1-01-1-3-3', '1-01-1-4-1', '1-01-1-4-2', '1-01-1-4-3',
        '1-01-1-5-1', '1-01-1-5-2', '1-01-1-5-3', '1-01-1-6-1', '1-01-1-6-2', '1-01-1-6-3',
        '1-01-1-7-1', '1-01-1-7-2', '1-01-1-7-3', '1-01-1-8-1', '1-01-1-8-2', '1-01-2-1-1',
        '1-01-2-1-2', '1-01-2-1-3', '1-01-2-2-1', '1-01-2-2-2', '1-01-2-2-3', '1-01-2-3-1',
        '1-01-2-3-2', '1-01-2-3-3', '1-01-2-4-1', '1-01-2-4-2', '1-01-2-4-3', '1-01-2-5-1',
        '1-01-2-5-2', '1-01-2-5-3', '1-02-1-1-1', '1-02-1-1-2', '1-02-1-1-3', '1-02-1-2-1',
        '1-02-1-2-2', '1-02-1-2-3', '1-02-1-3-1', '1-02-1-3-2', '1-02-1-3-3', '1-02-1-4-2',
        '1-02-1-4-3', '1-02-1-5-2', '1-02-1-5-3', '1-02-2-1-1', '1-02-2-1-2', '1-02-2-1-3',
        '1-02-2-2-1', '1-02-2-2-2', '1-02-2-2-3', '1-02-2-3-1', '1-02-2-3-2', '1-02-2-3-3',
        '1-02-2-4-1', '1-02-2-4-2', '1-02-2-4-3', '1-02-2-5-1', '1-02-2-5-2', '1-02-2-5-3',
        '1-03-1-1-1', '1-03-1-1-2', '1-03-1-1-3', '1-03-1-2-1', '1-03-1-2-2', '1-03-1-2-3',
        '1-03-1-3-1', '1-03-1-3-2', '1-03-1-3-3', '1-03-1-4-1', '1-03-1-4-2', '1-03-1-4-3',
        '1-03-1-5-1', '1-03-1-5-2', '1-03-1-5-3', '1-03-2-1-1', '1-03-2-1-2', '1-03-2-1-3',
        '1-03-2-2-1', '1-03-2-2-2', '1-03-2-2-3', '1-03-2-3-1', '1-03-2-3-2', '1-03-2-3-3',
        '1-03-2-4-2', '1-03-2-4-3', '1-04-1-1-1', '1-04-1-1-2', '1-04-1-1-3', '1-04-1-2-1',
        '1-04-1-2-2', '1-04-1-2-3', '1-04-1-3-1', '1-04-1-3-2', '1-04-1-4-3', '1-04-2-1-1',
        '1-04-2-1-2', '1-04-2-1-3', '1-04-2-1-4', '1-04-2-2-1', '1-04-2-2-2', '1-04-2-2-3',
        '1-04-2-2-4', '1-04-2-3-1', '1-04-2-3-2', '1-04-2-3-3', '1-05-2-1-1', '1-05-2-1-2',
        '1-05-2-2-1', '1-05-2-3-1', '1-05-2-3-2', '1-05-2-4-1', '1-05-2-4-3', '1-05-2-5-1',
        '1-05-2-5-2', '1-05-2-5-3', '1-06-1-1-1', '1-06-2-1-2', '1-06-2-2-1', '1-06-2-3-1',
        '1-07-1-1-1', '1-07-1-1-2', '1-07-1-2-1', '1-07-1-2-2', '1-07-1-3-2', '1-08-1-1-1',
        '1-08-1-1-2', '1-08-1-2-1', '1-08-1-2-2', '1-08-1-3-1', '1-09-1-1-1', '1-09-1-1-2',
        '1-09-1-2-1', '1-09-1-2-2', '1-09-1-3-1', '1-09-1-3-2', '1-09-2-1-1', '1-09-2-2-1',
        '1-09-2-3-1', '1-10-1-1-1', '1-10-2-1-1', '1-10-2-1-2', '1-10-2-2-1', '1-10-2-2-2',
        '1-10-2-3-1', '1-10-2-3-2', '1-11-1-2-1', '1-11-2-1-1', '1-11-2-1-2', '1-11-2-2-1',
        '1-11-2-3-1', '1-11-2-3-2', '1-13-1-1-1', '1-13-1-2-1', '1-13-1-3-1',
        '2-01-1-1-1', '2-01-1-2-1', '2-01-1-2-2', '2-01-1-2-3', '2-01-1-3-2', '2-01-1-3-3',
        '2-01-2-1-2', '2-01-2-3-1', '2-02-1-1-1', '2-02-1-3-3', '2-02-1-6-1', '2-02-2-1-1',
        '2-02-2-1-2', '2-02-2-1-3', '2-02-2-2-1', '2-02-2-2-2', '2-02-2-3-1', '2-02-2-3-3',
        '2-02-2-4-1', '2-03-1-6-1', '2-04-2-1-1', '2-04-2-1-2', '2-04-2-1-3', '2-04-2-2-1',
        '2-04-2-2-2', '2-04-2-2-3', '2-04-2-3-1', '2-04-2-3-3',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar tablas de warehouse_locations y warehouse_location_sections
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        WarehouseLocationSection::truncate();
        WarehouseLocation::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // 1. Crear estilos primero
        $styles = $this->createStyles();

        // 2. Obtener o crear warehouse y floor
        $warehouse = $this->getOrCreateWarehouse();
        $floor = $this->getOrCreateFloor($warehouse);

        // 3. Analizar y agrupar ubicaciones
        $locations = $this->groupLocationsBySections();

        // 4. Crear ubicaciones con sus secciones
        $this->createLocations($warehouse, $floor, $locations, $styles);

        $totalLocations = count($locations);
        $totalSections = count($this->sections);
        $this->command->info("\n✓ Se crearon {$totalLocations} ubicaciones con {$totalSections} secciones en Coruña Piso 1");
    }

    /**
     * Crear estilos de ubicaciones
     */
    private function createStyles(): array
    {
        $styles = [];

        // Estilo 1: Simple (1 sección)
        $styles['simple'] = WarehouseLocationStyle::firstOrCreate(
            ['code' => 'STY-SIMPLE'],
            [
                'uid' => Str::uuid(),
                'name' => 'Ubicación Simple',
                'description' => 'Ubicación con 1 sección y 1 nivel',
                'faces' => ['front'],
                'default_levels' => 1,
                'default_sections' => 1,
                'available' => true,
            ]
        );

        // Estilo 2: Multi-nivel 2-3 secciones
        $styles['multi-2-3'] = WarehouseLocationStyle::firstOrCreate(
            ['code' => 'STY-MULTI-2-3'],
            [
                'uid' => Str::uuid(),
                'name' => 'Estantería Multi-nivel 2-3',
                'description' => 'Estantería con 2-3 secciones',
                'faces' => ['front'],
                'default_levels' => 2,
                'default_sections' => 3,
                'available' => true,
            ]
        );

        // Estilo 3: Multi-nivel 4 secciones
        $styles['multi-4'] = WarehouseLocationStyle::firstOrCreate(
            ['code' => 'STY-MULTI-4'],
            [
                'uid' => Str::uuid(),
                'name' => 'Estantería Multi-nivel 4',
                'description' => 'Estantería con 4 secciones',
                'faces' => ['front'],
                'default_levels' => 4,
                'default_sections' => 4,
                'available' => true,
            ]
        );

        // Estilo 4: Grande 5+ secciones
        $styles['grande'] = WarehouseLocationStyle::firstOrCreate(
            ['code' => 'STY-GRANDE'],
            [
                'uid' => Str::uuid(),
                'name' => 'Estantería Grande',
                'description' => 'Estantería grande con 5 o más secciones',
                'faces' => ['front'],
                'default_levels' => 5,
                'default_sections' => 5,
                'available' => true,
            ]
        );

        return $styles;
    }

    /**
     * Obtener warehouse Coruña
     */
    private function getOrCreateWarehouse()
    {
        // Intentar obtener por COR primero (código actual en BD)
        $warehouse = Warehouse::where('code', 'COR')->first();

        if ($warehouse) {
            return $warehouse;
        }

        // Intentar por COR001 (código alternativo)
        $warehouse = Warehouse::where('code', 'COR001')->first();

        if ($warehouse) {
            return $warehouse;
        }

        // Si no existe, crear el warehouse
        return Warehouse::create([
            'uid' => Str::uuid(),
            'name' => 'Almacén Coruña',
            'code' => 'COR',
            'description' => 'Almacén de Coruña',
            'available' => true,
        ]);
    }

    /**
     * Obtener Floor 1 Coruña
     */
    private function getOrCreateFloor($warehouse)
    {
        // Intentar obtener por level 1
        $floor = WarehouseFloor::where('warehouse_id', $warehouse->id)
            ->where('level', 1)
            ->first();

        if ($floor) {
            return $floor;
        }

        // Intentar obtener por código PS1
        $floor = WarehouseFloor::where('warehouse_id', $warehouse->id)
            ->where('code', 'PS1')
            ->first();

        if ($floor) {
            return $floor;
        }

        // Si no existe, crear el floor
        return WarehouseFloor::create([
            'uid' => Str::uuid(),
            'warehouse_id' => $warehouse->id,
            'code' => 'PS1',
            'name' => 'PISO 1',
            'level' => 1,
            'available' => true,
        ]);
    }

    /**
     * Agrupar secciones por ubicación
     * Análisis:
     * - ALTO-BUNKER, BUNKER, REMOLQUES, SALTO-BUNKER, 1-FRONTALGOLF, PB-1-S-1 → 1 sección
     * - 1-CANAS-1 a 1-CANAS-12 → Cada uno es una ubicación individual con 1 sección
     * - Resto: Agrupar por código base (eliminar último número)
     */
    private function groupLocationsBySections(): array
    {
        $locations = [];
        $simpleNames = ['ALTO-BUNKER', 'BUNKER', 'REMOLQUES', 'SALTO-BUNKER', '1-FRONTALGOLF', 'PB-1-S-1'];

        foreach ($this->sections as $code) {
            // Si es ubicación simple
            if (in_array($code, $simpleNames)) {
                $locations[$code] = [
                    'code' => $code,
                    'levels' => [1],
                    'section_count' => 1,
                ];
                continue;
            }

            // Si es CANAS (1-CANAS-X)
            if (preg_match('/^1-CANAS-\d+$/', $code)) {
                $locations[$code] = [
                    'code' => $code,
                    'levels' => [1],
                    'section_count' => 1,
                ];
                continue;
            }

            // Resto: Agrupar por código base
            $lastDash = strrpos($code, '-');
            $lastPart = substr($code, $lastDash + 1);

            if (is_numeric($lastPart)) {
                $locationCode = substr($code, 0, $lastDash);
                $level = (int)$lastPart;
            } else {
                // Sin número final (no debería llegar aquí)
                $locationCode = $code;
                $level = 1;
            }

            if (!isset($locations[$locationCode])) {
                $locations[$locationCode] = [
                    'code' => $locationCode,
                    'levels' => [],
                ];
            }

            if (!in_array($level, $locations[$locationCode]['levels'])) {
                $locations[$locationCode]['levels'][] = $level;
            }
        }

        // Calcular section_count y determinar estilo
        foreach ($locations as $code => &$location) {
            $location['section_count'] = count($location['levels']);
        }

        return $locations;
    }

    /**
     * Determinar estilo según número de secciones
     */
    private function getStyleForSectionCount(int $count, array $styles)
    {
        return match(true) {
            $count == 1 => $styles['simple'],
            $count <= 3 => $styles['multi-2-3'],
            $count == 4 => $styles['multi-4'],
            default => $styles['grande'],
        };
    }

    /**
     * Crear ubicaciones y sus secciones
     */
    private function createLocations($warehouse, $floor, $locations, $styles): void
    {
        $posX = 1;
        $posY = 1;

        foreach ($locations as $locationData) {
            $code = $locationData['code'];
            $levels = $locationData['levels'];
            $sectionCount = $locationData['section_count'];

            // Verificar si ya existe
            $existing = WarehouseLocation::where('warehouse_id', $warehouse->id)
                ->where('floor_id', $floor->id)
                ->where('code', $code)
                ->first();

            if ($existing) {
                continue;
            }

            // Determinar estilo
            $style = $this->getStyleForSectionCount($sectionCount, $styles);

            // Crear ubicación
            $location = WarehouseLocation::create([
                'uid' => Str::uuid(),
                'warehouse_id' => $warehouse->id,
                'floor_id' => $floor->id,
                'code' => $code,
                'style_id' => $style->id,
                'position_x' => $posX,
                'position_y' => $posY,
                'total_levels' => max($levels),
                'available' => true,
                'notes' => "Ubicación {$code} - Coruña Planta 1",
            ]);

            // Crear secciones ordenadas
            sort($levels);
            foreach ($levels as $level) {
                WarehouseLocationSection::create([
                    'uid' => Str::uuid(),
                    'location_id' => $location->id,
                    'code' => "{$code}-{$level}",
                    'barcode' => "{$code}-L{$level}",
                    'level' => $level,
                    'face' => null,
                    'available' => true,
                    'notes' => "Sección nivel {$level} de {$code}",
                ]);
            }

            // Incrementar posición
            $posX += 0.5;
        }
    }
}
