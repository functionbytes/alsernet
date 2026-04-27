<?php

namespace Modules\Ecommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Models\Tax;
use Modules\Ecommerce\Models\TaxRule;

class TaxRuleFactory extends Factory
{
    protected $model = TaxRule::class;

    public function definition(): array
    {
        return [
            'tax_id' => Tax::factory(),
            'name' => fake()->words(2, true),
            'basis' => 'price',
            'price_from' => 0,
            'price_to' => null,
            'percentage' => fake()->randomFloat(2, 0, 20),
            'order' => 0,
        ];
    }
}
