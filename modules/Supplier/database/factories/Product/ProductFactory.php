<?php

namespace Modules\Supplier\Database\Factories\Product;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Supplier\Models\Category\Category;
use Modules\Supplier\Models\Product\Product;
use Modules\Supplier\Models\Supplier\Supplier;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'erp_id' => fake()->unique()->randomNumber(6),
            'supplier_id' => Supplier::factory(),
            'category_id' => Category::factory(),
            'erp_category_id' => fake()->optional()->randomNumber(5),
            'code' => fake()->unique()->bothify('PROD-####'),
            'name' => fake()->words(3, true),
            'available' => true,
            'is_default' => false,
            'web_published' => false,
            'erp_model_id' => fake()->optional()->randomNumber(5),
            'metadata' => null,
            'last_sync_at' => now()->subDays(1),
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

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }
}
