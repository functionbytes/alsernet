<?php

namespace Modules\Engagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Engagement\Models\ConversionGoal;

class ConversionGoalFactory extends Factory
{
    protected $model = ConversionGoal::class;

    public function definition(): array
    {
        return [
            'inbox_id' => 1,
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->optional()->sentence(),
            'event_name' => $this->faker->randomElement(['purchase', 'signup', 'add_to_cart', 'contact']),
            'selector' => null,
            'url_pattern' => null,
            'value' => $this->faker->randomFloat(2, 10, 500),
            'currency' => 'EUR',
            'is_active' => true,
        ];
    }

    public function urlBased(): static
    {
        return $this->state([
            'event_name' => null,
            'url_pattern' => '/gracias',
        ]);
    }
}
