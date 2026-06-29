<?php

namespace Modules\Ecommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Models\SpecificationTable;

class SpecificationTableFactory extends Factory
{
    protected $model = SpecificationTable::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
