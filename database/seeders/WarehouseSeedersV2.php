<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Warehouse\WarehouseFloor;
use App\Models\Warehouse\WarehouseLocationStyle;
use App\Models\Warehouse\WarehouseLocation;
use App\Models\Warehouse\WarehouseInventorySlot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WarehouseSeedersV2 extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar datos existentes
        echo "\n🗑️  Limpiando datos previos...\n";

        // Desactivar foreign key constraints
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Truncar en orden correcto (dependientes primero)
        try {
            // Eliminar todos los registros (más seguro que truncate)
            WarehouseInventorySlot::query()->delete();
            WarehouseLocation::query()->delete();
            WarehouseLocationStyle::query()->delete();
            WarehouseFloor::query()->delete();

            // Resetear auto-increment
            DB::statement('ALTER TABLE warehouse_inventory_slots AUTO_INCREMENT = 1;');
            DB::statement('ALTER TABLE warehouse_stands AUTO_INCREMENT = 1;');
            DB::statement('ALTER TABLE warehouse_stand_styles AUTO_INCREMENT = 1;');
            DB::statement('ALTER TABLE warehouse_floors AUTO_INCREMENT = 1;');

            echo "✅ Datos previos limpiados correctamente\n";
        } catch (\Exception $e) {
            echo "⚠️  Error al limpiar datos: " . $e->getMessage() . "\n";
        }

        // Reactivar foreign key constraints
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Crear pisos (Floors)
        echo "✅ Creando pisos...\n";
        $floors = [];
        foreach ([1, 2, 3] as $floorNum) {
            $floors[$floorNum] = WarehouseFloor::create([
                'uid' => Str::uuid(),
                'code' => "P{$floorNum}",
                'name' => "Piso {$floorNum}",
                'description' => "Piso número {$floorNum}",
                'available' => true,
                'order' => $floorNum,
            ]);
        }
        echo "✅ 3 pisos creados\n";

        // Crear estilos
        echo "✅ Creando estilos de estanterías...\n";
        $stylesByKind = [];

        // Estilo ROW
        $stylesByKind['row'] = WarehouseLocationStyle::create([
            'uid' => Str::uuid(),
            'code' => 'SHELF-ROW',
            'name' => 'Estantería Horizontal',
            'description' => 'Estantería tipo pasillo horizontal',
            'faces' => ['right', 'left'],
            'default_levels' => 1,
            'default_sections' => 5,
            'available' => true,
        ]);

        // Estilo COLUMNS
        $stylesByKind['columns'] = WarehouseLocationStyle::create([
            'uid' => Str::uuid(),
            'code' => 'SHELF-COLUMNS',
            'name' => 'Estantería Vertical',
            'description' => 'Estantería tipo columna vertical',
            'faces' => ['right', 'left'],
            'default_levels' => 7,
            'default_sections' => 1,
            'available' => true,
        ]);

        echo "✅ 2 estilos de estanterías creados\n";

        // Crear estantes y posiciones con distribución mejorada
        echo "✅ Creando estantes y posiciones de inventario...\n";
        $standCount = 0;
        $slotCount = 0;

        // Define layout
        $layout = $this->getLayoutDefinition();
        $pasilloIndex = 0;

        // Dimensiones del almacén (metros)
        $warehouseWidth = 42.23;
        $warehouseHeight = 30.26;
        $margin = 0.5;

        // Parámetros de distribución (deben coincidir con el front-end)
        $standWidthM = 2.5;      // Ancho del stand en metros
        $standHeightM = 2.5;     // Alto del stand en metros
        $spacingM = 0.3;         // Espaciado entre stands
        $cellSizeM = $standWidthM + $spacingM; // 2.8m por celda

        // Calcular cuántas filas y columnas caben
        $availableWidth = $warehouseWidth - (2 * $margin);
        $availableHeight = $warehouseHeight - (2 * $margin);
        $maxCols = (int)($availableWidth / $cellSizeM);
        $maxRows = (int)($availableHeight / $cellSizeM);

        echo "📐 Dimensiones: {$availableWidth}m × {$availableHeight}m\n";
        echo "📊 Grilla: {$maxCols} columnas × {$maxRows} filas\n\n";

        // Distribuir pasillos en secciones del almacén
        $pasilloPositions = $this->calculatePasilloPositions($layout, $maxCols, $maxRows, $margin, $cellSizeM);

        foreach ($layout as $pasilloConfig) {
            $pasillo = $pasilloConfig['id'];
            $pasilloFloors = $pasilloConfig['floors'];
            $kind = $pasilloConfig['kind'];
            $style = $stylesByKind[$kind];
            $itemsConfig = $pasilloConfig['items'] ?? [];

            // Obtener posición del pasillo
            $pasilloPos = $pasilloPositions[$pasilloIndex] ?? ['col' => 0, 'row' => 0];
            $baseX = $margin + ($pasilloPos['col'] * $cellSizeM);
            $baseY = $margin + ($pasilloPos['row'] * $cellSizeM);

            foreach ($pasilloFloors as $floorNum) {
                $floor = $floors[$floorNum];

                // Crear los estantes para este pasillo en este piso
                for ($i = 1; $i <= $pasilloConfig['count']; $i++) {
                    // Incluir el número de piso en el código para evitar duplicados
                    $standCode = "P{$floorNum}-{$pasillo}-{$i}";

                    // Calcular posición basada en la grilla
                    // Desplazar stands dentro del pasillo si hay múltiples stands
                    $offsetX = 0;
                    if ($pasilloConfig['count'] > 1 && $kind === 'row') {
                        // Para ROW, distribuir horizontalmente
                        $offsetX = ($i - 1) * $cellSizeM;
                    }

                    $position_x = $baseX + $offsetX;
                    $position_y = $baseY;

                    // Asegurar que no sobrepasa los límites
                    $position_x = min($position_x, $warehouseWidth - $margin);
                    $position_y = min($position_y, $warehouseHeight - $margin);

                    $stand = WarehouseLocation::create([
                        'uid' => Str::uuid(),
                        'floor_id' => $floor->id,
                        'stand_style_id' => $style->id,
                        'code' => $standCode,
                        'barcode' => strtoupper(Str::random(12)),
                        'position_x' => round($position_x, 2),
                        'position_y' => round($position_y, 2),
                        'position_z' => 0,
                        'total_levels' => $kind === 'row' ? 1 : 7,
                        'total_sections' => $kind === 'row' ? 5 : 1,
                        'capacity' => null,
                        'available' => true,
                        'notes' => "Stand {$i} del pasillo {$pasillo} - Piso {$floorNum} (Tipo: {$kind})",
                    ]);
                    $standCount++;

                    // Crear slots basados en itemsConfig
                    foreach ($itemsConfig as $indexOrRow => $facesData) {
                        foreach ($facesData as $face => $items) {
                            foreach ($items as $itemIdx => $item) {
                                // Generar barcode único: FLOOR-PASILLO-STAND-FACE-LEVEL-SECTION
                                $barcode = "F{$floorNum}-{$pasillo}-{$i}-{$face}-{$indexOrRow}-{$itemIdx}";

                                $slot = WarehouseInventorySlot::create([
                                    'uid' => Str::uuid(),
                                    'stand_id' => $stand->id,
                                    'product_id' => null,
                                    'face' => $face,
                                    'level' => $indexOrRow,
                                    'section' => $itemIdx + 1,
                                    'barcode' => strtoupper($barcode),
                                    'quantity' => 0,
                                    'max_quantity' => 10,
                                    'weight_current' => 0,
                                    'weight_max' => 100,
                                    'is_occupied' => false,
                                    'last_movement' => now(),
                                ]);
                                $slotCount++;
                            }
                        }
                    }
                }
            }

            $pasilloIndex++;
        }

        echo "\n✅ {$standCount} estantes creados\n";
        echo "✅ {$slotCount} posiciones de inventario creadas\n";
        echo "\n✅ ¡Sistema de almacén sembrado exitosamente!\n\n";
    }

    /**
     * Calcula las posiciones de los pasillos en una grilla inteligente
     */
    private function calculatePasilloPositions($layout, $maxCols, $maxRows, $margin, $cellSizeM)
    {
        $positions = [];
        $col = 0;
        $row = 0;

        foreach ($layout as $index => $pasilloConfig) {
            // Si es ROW y tiene múltiples stands, ocupa múltiples columnas
            $width = $pasilloConfig['kind'] === 'row' ? $pasilloConfig['count'] : 1;

            // Si supera el límite de columnas, pasar a siguiente fila
            if ($col + $width > $maxCols) {
                $col = 0;
                $row += 2; // Dejar espacio entre filas de pasillos
            }

            $positions[$index] = [
                'col' => $col,
                'row' => $row,
                'id' => $pasilloConfig['id'],
                'kind' => $pasilloConfig['kind'],
            ];

            // Avanzar columna
            $col += $width + 1; // +1 para espaciado entre pasillos
        }

        return $positions;
    }

    private function getLayoutDefinition()
    {
        $columnItems = [
            1 => ['right' => [
                ['code' => 'H1-1-AF', 'color' => 'shelf--rojo'],
                ['code' => 'H1-1-B', 'color' => 'shelf--rojo'],
                ['code' => 'H1-1-C', 'color' => 'shelf--rojo'],
            ]],
            2 => ['left' => [
                ['code' => 'H1-2-X', 'color' => 'shelf--rojo'],
                ['code' => 'H1-2-Y', 'color' => 'shelf--verde'],
                ['code' => 'H1-2-Z', 'color' => 'shelf--verde'],
            ]],
            3 => ['right' => [
                ['code' => 'H1-3-U', 'color' => 'shelf--morado'],
                ['code' => 'H1-3-V', 'color' => 'shelf--rojo'],
            ]],
            4 => [
                'right' => [
                    ['code' => 'H1-4-01', 'color' => 'shelf--azul'],
                    ['code' => 'H1-4-02', 'color' => 'shelf--azul'],
                    ['code' => 'H1-4-03', 'color' => 'shelf--rojo'],
                    ['code' => 'H1-4-04', 'color' => 'shelf--rojo'],
                ],
                'left' => [
                    ['code' => 'H1-4-L1', 'color' => 'shelf--ambar'],
                    ['code' => 'H1-4-L2', 'color' => 'shelf--morado'],
                ]
            ],
            5 => ['right' => [
                ['code' => 'H1-5-A1', 'color' => 'shelf--verde'],
            ]],
        ];

        $columnItemsSmall = [
            1 => ['right' => [
                ['code' => 'H1-1-AF', 'color' => 'shelf--rojo'],
                ['code' => 'H1-1-B', 'color' => 'shelf--rojo'],
                ['code' => 'H1-1-C', 'color' => 'shelf--rojo'],
            ]],
            2 => ['left' => [
                ['code' => 'H1-2-X', 'color' => 'shelf--rojo'],
                ['code' => 'H1-2-Y', 'color' => 'shelf--verde'],
                ['code' => 'H1-2-Z', 'color' => 'shelf--verde'],
            ]],
            3 => ['right' => [
                ['code' => 'H1-3-U', 'color' => 'shelf--morado'],
                ['code' => 'H1-3-V', 'color' => 'shelf--rojo'],
            ]],
        ];

        $horizontalItems = [
            1 => ['right' => [
                ['code' => 'H1-1-Aa', 'color' => 'shelf--gris'],
                ['code' => 'H1-1-B', 'color' => 'shelf--gris'],
                ['code' => 'H1-1-C', 'color' => 'shelf--gris'],
            ]],
            2 => ['left' => [
                ['code' => 'H1-2-X', 'color' => 'shelf--ambar'],
                ['code' => 'H1-2-Y', 'color' => 'shelf--verde'],
                ['code' => 'H1-2-Z', 'color' => 'shelf--azul'],
            ]],
            3 => ['left' => [
                ['code' => 'H1-2-X', 'color' => 'shelf--verde'],
                ['code' => 'H1-2-Y', 'color' => 'shelf--verde'],
                ['code' => 'H1-2-Z', 'color' => 'shelf--verde'],
            ]],
            4 => [
                'right' => [
                    ['code' => 'H1-4-01', 'color' => 'shelf--verde'],
                    ['code' => 'H1-4-02', 'color' => 'shelf--verde'],
                    ['code' => 'H1-4-03', 'color' => 'shelf--verde'],
                    ['code' => 'H1-4-04', 'color' => 'shelf--verde'],
                ],
                'left' => [
                    ['code' => 'H1-4-L1', 'color' => 'shelf--verde'],
                    ['code' => 'H1-4-L2', 'color' => 'shelf--verde'],
                ]
            ],
            5 => ['right' => [
                ['code' => 'H1-5-A1', 'color' => 'shelf--verde'],
            ]],
        ];

        return [
            // HORIZONTAL SHELVES (ROW type)
            [
                'id' => 'PASILLO13A',
                'floors' => [1, 2, 3],
                'kind' => 'row',
                'count' => 5,
                'items' => $horizontalItems,
            ],
            [
                'id' => 'PASILLO13B',
                'floors' => [1],
                'kind' => 'row',
                'count' => 3,
                'items' => [
                    1 => ['right' => [
                        ['code' => 'H1-1-AF', 'color' => 'shelf--rojo'],
                        ['code' => 'H1-1-B', 'color' => 'shelf--rojo'],
                        ['code' => 'H1-1-C', 'color' => 'shelf--rojo'],
                    ]],
                    2 => ['left' => [
                        ['code' => 'H1-2-X', 'color' => 'shelf--rojo'],
                        ['code' => 'H1-2-Y', 'color' => 'shelf--verde'],
                        ['code' => 'H1-2-Z', 'color' => 'shelf--verde'],
                    ]],
                    3 => ['right' => [
                        ['code' => 'H1-3-U', 'color' => 'shelf--morado'],
                        ['code' => 'H1-3-V', 'color' => 'shelf--rojo'],
                    ]],
                ],
            ],
            [
                'id' => 'PASILLO13C',
                'floors' => [1],
                'kind' => 'row',
                'count' => 5,
                'items' => $horizontalItems,
            ],
            [
                'id' => 'PASILLO13D',
                'floors' => [1],
                'kind' => 'row',
                'count' => 5,
                'items' => $horizontalItems,
            ],
            // VERTICAL COLUMNS
            ['id' => 'PASILLO1', 'floors' => [1], 'kind' => 'columns', 'count' => 1, 'items' => $columnItems],
            ['id' => 'PASILLO2', 'floors' => [1], 'kind' => 'columns', 'count' => 1, 'items' => $columnItems],
            ['id' => 'PASILLO3', 'floors' => [1], 'kind' => 'columns', 'count' => 1, 'items' => $columnItems],
            ['id' => 'PASILLO4', 'floors' => [1], 'kind' => 'columns', 'count' => 1, 'items' => $columnItems],
            ['id' => 'PASILLO5', 'floors' => [1], 'kind' => 'columns', 'count' => 1, 'items' => $columnItems],
            ['id' => 'PASILLO6', 'floors' => [1], 'kind' => 'columns', 'count' => 1, 'items' => $columnItemsSmall],
            ['id' => 'PASILLO7', 'floors' => [1], 'kind' => 'columns', 'count' => 1, 'items' => $columnItemsSmall],
            ['id' => 'PASILLO8', 'floors' => [1], 'kind' => 'columns', 'count' => 1, 'items' => $columnItemsSmall],
            ['id' => 'PASILLO9', 'floors' => [1], 'kind' => 'columns', 'count' => 1, 'items' => $columnItemsSmall],
            ['id' => 'PASILLO10', 'floors' => [1], 'kind' => 'columns', 'count' => 1, 'items' => $columnItems],
            ['id' => 'PASILLO11', 'floors' => [1], 'kind' => 'columns', 'count' => 1, 'items' => $columnItems],
            ['id' => 'PASILLO12', 'floors' => [1], 'kind' => 'columns', 'count' => 1, 'items' => $columnItems],
        ];
    }
}
