<?php

namespace Modules\Ecommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Models\Cart;
use Modules\Ecommerce\Models\Product;

class CartFactory extends Factory
{
    protected $model = Cart::class;

    public function definition(): array
    {
        return [
            'customer_id' => null,
            'session_id' => fake()->uuid(),
            'product_id' => Product::factory(),
            'qty' => fake()->numberBetween(1, 5),
            'options' => null,
        ];
    }
}
