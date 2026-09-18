<?php

namespace Modules\Supplier\Database\Factories\Product;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Supplier\Models\Category\Category;
use Modules\Supplier\Models\Product\Product;
use Modules\Supplier\Models\Product\ProductAttribute;

class ProductAttributeFactory extends Factory
{
    protected $model = ProductAttribute::class;

    public function definition(): array
    {
        return [
            'erp_id' => fake()->unique()->randomNumber(7),
            'product_id' => Product::factory(),
            'category_id' => Category::factory(),
            'erp_category_id' => fake()->optional()->randomNumber(5),
            'erp_group_id' => fake()->optional()->randomNumber(5),
            'code' => fake()->unique()->bothify('ATTR-####'),
            'code_secundary' => fake()->optional()->bothify('SEC-####'),
            'reference' => fake()->optional()->bothify('REF-#####'),
            'ean13' => fake()->optional()->ean13(),
            'upc' => fake()->optional()->numerify('############'),
            'name' => fake()->words(4, true),
            'available' => true,
            'web_published' => false,
            'erp_created_at' => now()->subDays(fake()->numberBetween(1, 30)),
            'erp_updated_at' => now()->subDays(fake()->numberBetween(0, 5)),
            'last_sync_at' => now()->subDay(),
        ];
    }

    public function unavailable(): static
    {
        return $this->state(['available' => false]);
    }

    public function webPublished(): static
    {
        return $this->state(['web_published' => true]);
    }
}
