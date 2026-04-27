<?php

namespace Modules\Ecommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Models\GlobalOption;

class GlobalOptionFactory extends Factory
{
    protected $model = GlobalOption::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'option_type' => fake()->randomElement(['text', 'dropdown', 'checkbox', 'radio']),
            'required' => fake()->boolean(),
            'order' => fake()->numberBetween(0, 100),
        ];
    }
}
