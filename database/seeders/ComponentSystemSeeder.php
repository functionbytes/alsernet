<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\ComponentSubstitution;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class ComponentSystemSeeder extends Seeder
{
    public function run(): void
    {
        // Crear proveedores de ejemplo
        $suppliers = $this->createSuppliers();

        // Crear componentes para productos existentes
        $this->createProductComponents($suppliers);

        // Crear sustituciones de ejemplo
        $this->createComponentSubstitutions();
    }

    protected function createSuppliers(): array
    {
        $suppliers = [
            [
                'name' => 'TechParts Supply Co.',
                'code' => 'TECHPARTS',
                'email' => 'orders@techparts.com',
                'phone' => '+1-555-0123',
                'is_active' => true,
            ],
            [
                'name' => 'Electronic Components Ltd.',
                'code' => 'ECOMP',
                'email' => 'supply@ecomp.com',
                'phone' => '+1-555-0124',
                'is_active' => true,
            ],
            [
                'name' => 'Global Parts Distribution',
                'code' => 'GLOBPARTS',
                'email' => 'sales@globparts.com',
                'phone' => '+1-555-0125',
                'is_active' => true,
            ],
        ];

        $supplierModels = [];
        foreach ($suppliers as $supplierData) {
            $supplierModels[] = Supplier::create($supplierData);
        }

        return $supplierModels;
    }

    protected function createProductComponents($suppliers): void
    {
        $products = Product::limit(10)->get();

        foreach ($products as $product) {
            $this->createComponentsForProduct($product, $suppliers);
        }
    }

    protected function createComponentsForProduct($product, $suppliers): void
    {
        // Componentes esenciales
        $essentialComponents = [
            [
                'name' => 'Adaptador de corriente',
                'code' => 'PWR-ADAPT-' . $product->id,
                'sku' => 'PWR-' . strtoupper(uniqid()),
                'category' => ProductComponent::CATEGORY_ELECTRONICS,
                'type' => ProductComponent::TYPE_ESSENTIAL,
                'quantity_per_product' => 1,
                'unit_cost' => 15.00,
                'replacement_cost' => 20.00,
                'weight' => 0.3,
                'current_stock' => rand(50, 200),
                'minimum_stock' => 20,
                'reorder_point' => 30,
                'deduction_percentage' => 10.00,
                'affects_functionality' => true,
            ],
            [
                'name' => 'Cable USB',
                'code' => 'USB-CABLE-' . $product->id,
                'sku' => 'USB-' . strtoupper(uniqid()),
                'category' => ProductComponent::CATEGORY_ELECTRONICS,
                'type' => ProductComponent::TYPE_ESSENTIAL,
                'quantity_per_product' => 1,
                'unit_cost' => 8.00,
                'replacement_cost' => 12.00,
                'weight' => 0.1,
                'current_stock' => rand(100, 300),
                'minimum_stock' => 50,
                'reorder_point' => 75,
                'deduction_percentage' => 5.00,
                'affects_functionality' => true,
            ],
        ];

        // Componentes opcionales
        $optionalComponents = [
            [
                'name' => 'Manual de usuario',
                'code' => 'MANUAL-' . $product->id,
                'sku' => 'MAN-' . strtoupper(uniqid()),
                'category' => ProductComponent::CATEGORY_ACCESSORY,
                'type' => ProductComponent::TYPE_OPTIONAL,
                'quantity_per_product' => 1,
                'unit_cost' => 2.00,
                'replacement_cost' => 5.00,
                'weight' => 0.05,
                'current_stock' => rand(200, 500),
                'minimum_stock' => 100,
                'reorder_point' => 150,
                'deduction_percentage' => 0.00,
                'fixed_deduction_amount' => 2.00,
                'affects_functionality' => false,
            ],
            [
                'name' => 'Estuche protector',
                'code' => 'CASE-' . $product->id,
                'sku' => 'CASE-' . strtoupper(uniqid()),
                'category' => ProductComponent::CATEGORY_ACCESSORY,
                'type' => ProductComponent::TYPE_ACCESSORY,
                'quantity_per_product' => 1,
                'unit_cost' => 12.00,
                'replacement_cost' => 18.00,
                'weight' => 0.2,
                'current_stock' => rand(30, 100),
                'minimum_stock' => 15,
                'reorder_point' => 25,
                'deduction_percentage' => 8.00,
                'affects_functionality' => false,
            ],
        ];

        // Crear componentes esenciales
        foreach ($essentialComponents as $componentData) {
            $this->createComponent($product, $componentData, $suppliers);
        }

        // Crear componentes opcionales
        foreach ($optionalComponents as $componentData) {
            $this->createComponent($product, $componentData, $suppliers);
        }
    }

    protected function createComponent($product, $componentData, $suppliers): void
    {
        $supplier = $suppliers[array_rand($suppliers)];

        $component = ProductComponent::create(array_merge($componentData, [
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
            'supplier_sku' => 'SUP-' . strtoupper(uniqid()),
            'lead_time_days' => rand(3, 14),
            'dimensions' => [
                'length' => rand(5, 30),
                'width' => rand(5, 20),
                'height' => rand(1, 10),
            ],
            'is_trackable' => rand(0, 1) == 1,
            'has_serial_numbers' => $componentData['type'] === ProductComponent::TYPE_ESSENTIAL && rand(0, 1) == 1,
            'is_replaceable' => true,
            'compatibility_level' => 'strict',
            'location' => 'A' . rand(1, 10) . '-' . rand(1, 20),
            'metadata' => [
                'is_fragile' => rand(0, 1) == 1,
                'requires_special_handling' => false,
                'packaging_type' => 'standard',
            ],
        ]));

        // Crear algunos movimientos de stock iniciales
        $this->createInitialStockMovements($component);
    }

    protected function createInitialStockMovements($component): void
    {
        // Movimiento inicial de entrada
        $component->stockMovements()->create([
            'movement_type' => 'in',
            'reference_type' => 'initial_stock',
            'quantity' => $component->current_stock,
            'stock_before' => 0,
            'stock_after' => $component->current_stock,
            'unit_cost' => $component->unit_cost,
            'total_cost' => $component->unit_cost * $component->current_stock,
            'reason' => 'Stock inicial del sistema',
            'movement_date' => now()->subDays(rand(1, 30)),
        ]);

        // Algunos movimientos aleatorios
        for ($i = 0; $i < rand(2, 5); $i++) {
            $isOut = rand(0, 1) == 1;
            $quantity = rand(1, 20);

            if ($isOut) {
                $quantity = -$quantity;
                $movementType = 'out';
                $reason = 'Venta de producto';
            } else {
                $movementType = 'in';
                $reason = 'Reposición de stock';
            }

            $stockBefore = $component->current_stock;
            $stockAfter = $stockBefore + $quantity;

            if ($stockAfter >= 0) {
                $component->stockMovements()->create([
                    'movement_type' => $movementType,
                    'reference_type' => 'manual',
                    'quantity' => $quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'unit_cost' => $component->unit_cost,
                    'total_cost' => abs($quantity) * $component->unit_cost,
                    'reason' => $reason,
                    'movement_date' => now()->subDays(rand(1, 15)),
                ]);

                $component->update(['current_stock' => $stockAfter]);
            }
        }
    }

    protected function createComponentSubstitutions(): void
    {
        // Buscar componentes similares para crear sustituciones
        $usbCables = ProductComponent::where('name', 'like', '%USB%')->get();
        $adapters = ProductComponent::where('name', 'like', '%Adaptador%')->get();
        $cases = ProductComponent::where('name', 'like', '%Estuche%')->get();

        // Crear sustituciones para cables USB
        $this->createSubstitutionsForGroup($usbCables, 'direct', 'exact');

        // Crear sustituciones para adaptadores
        $this->createSubstitutionsForGroup($adapters, 'compatible', 'high');

        // Crear sustituciones para estuches
        $this->createSubstitutionsForGroup($cases, 'compatible', 'medium');
    }

    protected function createSubstitutionsForGroup($components, $substitutionType, $compatibilityLevel): void
    {
        $componentArray = $components->toArray();

        for ($i = 0; $i < count($componentArray); $i++) {
            for ($j = $i + 1; $j < count($componentArray); $j++) {
                if ($i !== $j) {
                    $original = $componentArray[$i];
                    $substitute = $componentArray[$j];

                    // Crear sustitución bidireccional con probabilidad
                    if (rand(0, 100) < 60) { // 60% de probabilidad
                        ComponentSubstitution::create([
                            'original_component_id' => $original['id'],
                            'substitute_component_id' => $substitute['id'],
                            'substitution_type' => $substitutionType,
                            'compatibility_level' => $compatibilityLevel,
                            'cost_difference' => round(($substitute['unit_cost'] - $original['unit_cost']), 2),
                            'performance_impact' => rand(-10, 10),
                            'priority' => rand(1, 10),
                            'requires_approval' => $substitutionType === 'upgrade',
                            'is_active' => true,
                        ]);
                    }
                }
            }
        }
    }
}
