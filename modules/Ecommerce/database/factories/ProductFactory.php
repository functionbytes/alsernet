<?php

namespace Modules\Ecommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Models\Product;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->paragraph(),
            'content' => fake()->paragraphs(3, true),
            'status' => 'published',
            'sku' => fake()->unique()->ean8(),
            'price' => fake()->randomFloat(2, 10, 500),
            'sale_price' => null,
            'quantity' => fake()->numberBetween(0, 100),
            'with_storehouse_management' => true,
            'is_featured' => fake()->boolean(20),
            'stock_status' => 'in_stock',
        ];
    }
}
