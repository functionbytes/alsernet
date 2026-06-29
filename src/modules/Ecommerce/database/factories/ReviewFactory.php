<?php

namespace Modules\Ecommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\Review;

class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'customer_id' => Customer::factory(),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'star' => fake()->numberBetween(1, 5),
            'comment' => fake()->paragraph(),
            'is_verified_buyer' => fake()->boolean(),
            'status' => 'approved',
            'images' => null,
            'reply' => null,
            'replied_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function verified(): static
    {
        return $this->state(['is_verified_buyer' => true]);
    }
}
