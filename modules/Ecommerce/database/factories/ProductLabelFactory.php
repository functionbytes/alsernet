<?php

namespace Modules\Ecommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Models\ProductLabel;

class ProductLabelFactory extends Factory
{
    protected $model = ProductLabel::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'color' => fake()->hexColor(),
            'text_color' => '#FFFFFF',
            'status' => 'published',
        ];
    }
}
