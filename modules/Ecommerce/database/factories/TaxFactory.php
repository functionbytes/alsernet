<?php

namespace Modules\Ecommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Models\Tax;

class TaxFactory extends Factory
{
    protected $model = Tax::class;

    public function definition(): array
    {
        return [
            'title' => fake()->words(2, true),
            'percentage' => fake()->randomFloat(2, 0, 20),
            'priority' => 1,
            'status' => 'published',
        ];
    }
}
