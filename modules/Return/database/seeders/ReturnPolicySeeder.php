<?php

namespace seeders;

use App\Models\Return\ReturnPolicy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ReturnPolicySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds the return_policies table with default return policies by category.
     * Policies define the terms and conditions for returning products.
     *
     * Policy parameters:
     * - Days to return: Window for accepting returns after purchase
     * - Condition allowed: Whether item needs to be unused/unopened
     * - Restocking fee: Percentage deducted from refund
     * - Requires proof of purchase: Receipt or order confirmation
     * - Categories: Which product categories this policy applies to
     *
     * Depends on: Categories must exist (created by CategorySeeder)
     */
    public function run(): void
    {
        $policies = [
            [
                'uid' => Str::ulid(),
                'name' => 'Política Estándar - Calzado',
                'key' => 'standard_footwear',
                'description' => 'Devoluciones estándar para calzado. 30 días desde la compra.',
                'days_to_return' => 30,
                'condition_required' => 'unused',
                'restocking_fee_percentage' => 0,
                'requires_original_packaging' => false,
                'requires_receipt' => true,
                'categories' => json_encode(['calzado', 'zapatillas', 'botas', 'zapatos']),
                'notes' => 'Aplicable a todas las subcategorías de calzado',
                'is_active' => true,
                'priority' => 1,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'Política Estándar - Ropa',
                'key' => 'standard_clothing',
                'description' => 'Devoluciones estándar para ropa. 30 días desde la compra.',
                'days_to_return' => 30,
                'condition_required' => 'unused',
                'restocking_fee_percentage' => 0,
                'requires_original_packaging' => false,
                'requires_receipt' => true,
                'categories' => json_encode(['ropa', 'camisetas', 'pantalones', 'abrigos']),
                'notes' => 'Etiquetas deben estar intactas',
                'is_active' => true,
                'priority' => 2,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'Política Accesorios',
                'key' => 'standard_accessories',
                'description' => 'Devoluciones para accesorios. 15 días desde la compra.',
                'days_to_return' => 15,
                'condition_required' => 'unused',
                'restocking_fee_percentage' => 10,
                'requires_original_packaging' => true,
                'requires_receipt' => true,
                'categories' => json_encode(['accesorios', 'mochilas', 'gorras']),
                'notes' => 'Requiere embalaje original intacto',
                'is_active' => true,
                'priority' => 3,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'Política Extendida - Garantía',
                'key' => 'extended_warranty',
                'description' => 'Política extendida con garantía. 90 días desde la compra.',
                'days_to_return' => 90,
                'condition_required' => 'defective',
                'restocking_fee_percentage' => 0,
                'requires_original_packaging' => false,
                'requires_receipt' => true,
                'categories' => json_encode(['electronica', 'equipamiento']),
                'notes' => 'Solo para productos defectuosos o daño de manufactura',
                'is_active' => true,
                'priority' => 4,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'Política Promocional - Rebajas',
                'key' => 'promotional_sale',
                'description' => 'Política reducida para artículos en rebajas. 7 días.',
                'days_to_return' => 7,
                'condition_required' => 'unused',
                'restocking_fee_percentage' => 20,
                'requires_original_packaging' => true,
                'requires_receipt' => true,
                'categories' => json_encode(['*']), // All categories
                'notes' => 'Aplica a productos con descuento superior al 40%',
                'is_active' => true,
                'priority' => 5,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'Sin Devolución - Artículos Personalizados',
                'key' => 'no_return_custom',
                'description' => 'Artículos personalizados no aceptan devoluciones.',
                'days_to_return' => 0,
                'condition_required' => 'any',
                'restocking_fee_percentage' => 100,
                'requires_original_packaging' => false,
                'requires_receipt' => false,
                'categories' => json_encode([]),
                'notes' => 'Productos personalizados, grabados o modificados no se devuelven',
                'is_active' => true,
                'priority' => 6,
            ],
        ];

        foreach ($policies as $policy) {
            ReturnPolicy::firstOrCreate(
                ['key' => $policy['key']],
                $policy
            );
        }

        $this->command->info('✅ Return policies seeded successfully');
    }
}
