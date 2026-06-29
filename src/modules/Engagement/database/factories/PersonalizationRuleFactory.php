<?php

namespace Modules\Engagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Engagement\Models\PersonalizationRule;

class PersonalizationRuleFactory extends Factory
{
    protected $model = PersonalizationRule::class;

    public function definition(): array
    {
        return [
            'inbox_id' => 1,
            'name' => $this->faker->sentence(3),
            'selector' => '.btn-primary',
            'conditions' => null,
            'mutation' => ['op' => 'text', 'value' => 'Hablar con asesor'],
            'is_active' => true,
        ];
    }

    public function showBanner(): static
    {
        return $this->state([
            'name' => 'Banner descuento',
            'selector' => 'body',
            'mutation' => [
                'op' => 'insert_before',
                'html' => '<div class="offer">10% extra hoy</div>',
            ],
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
