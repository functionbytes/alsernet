<?php

namespace Database\Seeders;

use App\Models\ProductReturnRule;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductReturnRulesSeeder extends Seeder
{
    public function run(): void
    {
        // Regla global por defecto
        ProductReturnRule::create([
            'rule_type' => 'global',
            'is_returnable' => true,
            'return_period_days' => 30,
            'max_return_percentage' => 100.00,
            'requires_original_packaging' => false,
            'requires_receipt' => true,
            'allow_partial_return' => true,
            'priority' => 1,
            'is_active' => true,
        ]);

        // Reglas por categoría
        $electronicsCategory = Category::where('name', 'Electrónicos')->first();
        if ($electronicsCategory) {
            ProductReturnRule::create([
                'category_id' => $electronicsCategory->id,
                'rule_type' => 'category',
                'is_returnable' => true,
                'return_period_days' => 15,
                'max_return_percentage' => 90.00,
                'requires_original_packaging' => true,
                'requires_receipt' => true,
                'allow_partial_return' => false,
                'conditions' => [
                    ['type' => 'requires_unopened', 'value' => true],
                    ['type' => 'max_usage_percentage', 'value' => 5],
                ],
                'excluded_reasons' => ['changed_mind'],
                'special_instructions' => 'Los productos electrónicos deben estar en su empaque original y sin signos de uso.',
                'priority' => 10,
                'is_active' => true,
            ]);
        }

        $clothingCategory = Category::where('name', 'Ropa')->first();
        if ($clothingCategory) {
            ProductReturnRule::create([
                'category_id' => $clothingCategory->id,
                'rule_type' => 'category',
                'is_returnable' => true,
                'return_period_days' => 45,
                'max_return_percentage' => 100.00,
                'requires_original_packaging' => false,
                'requires_receipt' => true,
                'allow_partial_return' => true,
                'conditions' => [
                    ['type' => 'seasonal_restriction', 'months' => [12, 1, 2]],
                ],
                'special_instructions' => 'La ropa debe estar limpia y con etiquetas originales.',
                'priority' => 5,
                'is_active' => true,
            ]);
        }

        // Categoría de productos no retornables
        $foodCategory = Category::where('name', 'Alimentos')->first();
        if ($foodCategory) {
            ProductReturnRule::create([
                'category_id' => $foodCategory->id,
                'rule_type' => 'category',
                'is_returnable' => false,
                'max_return_percentage' => 0.00,
                'special_instructions' => 'Por razones de seguridad alimentaria, estos productos no pueden ser devueltos.',
                'priority' => 20,
                'is_active' => true,
            ]);
        }

        // Regla específica para un producto caro
        $expensiveProduct = Product::where('price', '>', 1000)->first();
        if ($expensiveProduct) {
            ProductReturnRule::create([
                'product_id' => $expensiveProduct->id,
                'rule_type' => 'product',
                'is_returnable' => true,
                'return_period_days' => 7,
                'max_return_percentage' => 85.00,
                'requires_original_packaging' => true,
                'requires_receipt' => true,
                'allow_partial_return' => false,
                'conditions' => [
                    ['type' => 'min_days_owned', 'value' => 1],
                    ['type' => 'requires_unopened', 'value' => true],
                ],
                'special_instructions' => 'Producto de alto valor requiere inspección especial.',
                'priority' => 50,
                'is_active' => true,
            ]);
        }
    }
}
