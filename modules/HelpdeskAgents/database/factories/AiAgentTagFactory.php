<?php

namespace Modules\HelpdeskAgents\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskAgents\Models\AiAgentTag;

class AiAgentTagFactory extends Factory
{
    protected $model = AiAgentTag::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'soporte', 'ventas', 'técnico', 'facturación', 'VIP',
                'urgente', 'escalado', 'nuevo', 'recurrente', 'premium',
            ]).'-'.fake()->numberBetween(1, 99),
            'description' => fake()->optional()->sentence(),
            'color' => fake()->hexColor(),
            'icon' => 'fas fa-tag',
            'system_prompt_addition' => fake()->optional()->sentence(),
            'priority' => fake()->numberBetween(1, 10),
            'is_active' => true,
            'metadata' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function highPriority(): static
    {
        return $this->state(['priority' => fake()->numberBetween(8, 10)]);
    }
}
