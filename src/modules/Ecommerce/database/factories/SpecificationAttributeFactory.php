<?php

namespace Modules\Ecommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Models\SpecificationAttribute;
use Modules\Ecommerce\Models\SpecificationGroup;

class SpecificationAttributeFactory extends Factory
{
    protected $model = SpecificationAttribute::class;

    public function definition(): array
    {
        return [
            'group_id' => SpecificationGroup::factory(),
            'name' => fake()->word(),
            'type' => fake()->randomElement(['text', 'textarea', 'select', 'checkbox', 'radio']),
            'options' => null,
            'default_value' => null,
        ];
    }
}
