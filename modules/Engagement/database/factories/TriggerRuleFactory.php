<?php

namespace Modules\Engagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Engagement\Models\TriggerRule;

class TriggerRuleFactory extends Factory
{
    protected $model = TriggerRule::class;

    public function definition(): array
    {
        return [
            'inbox_id' => 1,
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->sentence(),
            'conditions' => [
                'operator' => 'AND',
                'rules' => [
                    ['type' => 'time_on_page', 'operator' => '>', 'value' => 30],
                ],
            ],
            'action' => ['type' => 'open_chat'],
            'priority' => $this->faker->numberBetween(0, 100),
            'fires_per_session' => 1,
            'is_active' => true,
        ];
    }

    public function highValueCart(): static
    {
        return $this->state([
            'name' => 'Carrito > 100€',
            'conditions' => [
                'operator' => 'AND',
                'rules' => [
                    ['type' => 'context', 'key' => 'cartValue', 'operator' => '>', 'value' => 100],
                    ['type' => 'time_on_page', 'operator' => '>', 'value' => 30],
                ],
            ],
            'action' => ['type' => 'open_chat'],
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
