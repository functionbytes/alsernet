<?php

namespace Modules\PriceLabels\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\PriceLabels\Models\PriceLabelGeneration;
use Modules\PriceLabels\Models\PriceLabelTemplate;

class PriceLabelGenerationFactory extends Factory
{
    protected $model = PriceLabelGeneration::class;

    public function definition(): array
    {
        return [
            'price_label_template_id' => PriceLabelTemplate::factory(),
            'template_name' => $this->faker->words(3, true),
            'type' => $this->faker->randomElement(['vertical', 'horizontal']),
            'rows_count' => $this->faker->numberBetween(1, 40),
            'file_path' => 'pricelabels/generated/'.$this->faker->uuid().'.pdf',
            'file_name' => 'etiquetas-vertical-'.$this->faker->uuid().'.pdf',
            'generated_by' => null,
        ];
    }
}
