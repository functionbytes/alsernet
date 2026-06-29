<?php

namespace Modules\Ecommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Models\ProductCategory;

class ProductCategoryFactory extends Factory
{
    protected $model = ProductCategory::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'slug' => fake()->unique()->slug(),
            'parent_id' => null,
            'description' => fake()->sentence(),
            'status' => 'published',
            'order' => fake()->numberBetween(0, 100),
            'image' => null,
            'is_featured' => fake()->boolean(10),
        ];
    }

    public function child(): static
    {
        return $this->state(fn () => [
            'parent_id' => ProductCategory::factory(),
        ]);
    }
}
