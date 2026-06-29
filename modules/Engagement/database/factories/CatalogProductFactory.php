<?php

namespace Modules\Engagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Engagement\Models\CatalogProduct;

class CatalogProductFactory extends Factory
{
    protected $model = CatalogProduct::class;

    public function definition(): array
    {
        return [
            'inbox_id' => 1,
            'platform_product_id' => $this->faker->unique()->bothify('PROD-####'),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->randomFloat(2, 5, 500),
            'currency' => 'EUR',
            'url' => $this->faker->url(),
            'image_url' => 'https://placehold.co/400x400',
            'category' => $this->faker->randomElement(['electronics', 'clothing', 'home', 'sports']),
            'metadata' => [],
            'is_active' => true,
            'synced_at' => now(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
