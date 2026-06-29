<?php

namespace Modules\Ecommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Models\Shipping;
use Modules\Ecommerce\Models\ShippingRule;

class ShippingRuleFactory extends Factory
{
    protected $model = ShippingRule::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'shipping_id' => Shipping::factory(),
            'type' => 'based_on_price',
            'from' => 0,
            'to' => 0,
            'price' => fake()->randomFloat(2, 0, 50),
        ];
    }
}
