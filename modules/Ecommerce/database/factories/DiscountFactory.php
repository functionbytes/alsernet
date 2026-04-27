<?php

namespace Modules\Ecommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Enums\DiscountType;
use Modules\Ecommerce\Models\Discount;

class DiscountFactory extends Factory
{
    protected $model = Discount::class;

    public function definition(): array
    {
        return [
            'title' => fake()->words(3, true),
            'code' => strtoupper(fake()->unique()->bothify('COUP-####')),
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'quantity' => null,
            'total_used' => 0,
            'value' => fake()->randomFloat(2, 5, 50),
            'type' => DiscountType::FIXED,
            'target' => 'order',
            'min_order_price' => null,
            'is_active' => true,
            'description' => null,
        ];
    }

    public function percentage(): static
    {
        return $this->state([
            'type' => DiscountType::PERCENTAGE,
            'value' => fake()->numberBetween(5, 30),
        ]);
    }

    public function expired(): static
    {
        return $this->state([
            'end_date' => now()->subDay(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
