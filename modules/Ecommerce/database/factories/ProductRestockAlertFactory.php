<?php

namespace Modules\Ecommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductRestockAlert;

class ProductRestockAlertFactory extends Factory
{
    protected $model = ProductRestockAlert::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'email' => $this->faker->unique()->safeEmail(),
            'customer_id' => null,
            'notified_at' => null,
        ];
    }

    public function notified(): static
    {
        return $this->state(fn (array $attributes) => [
            'notified_at' => now(),
        ]);
    }
}
