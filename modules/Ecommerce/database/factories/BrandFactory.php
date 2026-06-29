<?php

namespace Modules\Ecommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Models\Brand;

class BrandFactory extends Factory
{
    protected $model = Brand::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->sentence(),
            'website' => fake()->optional()->url(),
            'logo' => null,
            'status' => 'published',
            'order' => fake()->numberBetween(0, 100),
            'is_featured' => fake()->boolean(20),
        ];
    }
}
