<?php

namespace Modules\PriceLabels\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\PriceLabels\Models\PriceLabelTemplate;

class PriceLabelTemplateFactory extends Factory
{
    protected $model = PriceLabelTemplate::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'is_active' => true,
            'orientation' => 'both',
            'label_text' => 'Precio recomendado:',
            'fields' => null,
            'positions_vertical' => null,
            'positions_horizontal' => null,
            'vertical_rows' => 2,
            'vertical_columns' => 2,
            'horizontal_rows' => 2,
            'horizontal_columns' => 4,
            'field_definitions' => null,
        ];
    }
}
