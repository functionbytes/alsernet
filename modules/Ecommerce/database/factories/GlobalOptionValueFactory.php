<?php

namespace Modules\Ecommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Models\GlobalOption;
use Modules\Ecommerce\Models\GlobalOptionValue;

class GlobalOptionValueFactory extends Factory
{
    protected $model = GlobalOptionValue::class;

    public function definition(): array
    {
        return [
            'option_id' => GlobalOption::factory(),
            'option_value' => fake()->word(),
            'affect_price' => fake()->randomFloat(2, 0, 100),
            'affect_type' => fake()->randomElement(['plus', 'minus', 'percent']),
            'order' => fake()->numberBetween(0, 10),
        ];
    }
}
