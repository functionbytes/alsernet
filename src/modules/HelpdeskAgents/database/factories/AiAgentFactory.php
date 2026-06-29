<?php

namespace Modules\HelpdeskAgents\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskAgents\Models\AiAgent;

class AiAgentFactory extends Factory
{
    protected $model = AiAgent::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'type' => 'chat',
            'provider' => fake()->randomElement(['openai', 'anthropic']),
            'model' => 'gpt-4o',
            'status' => 'active',
            'enabled_at' => now(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive', 'enabled_at' => null]);
    }

    public function paused(): static
    {
        return $this->state(['status' => 'paused']);
    }

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }

    public function openai(string $model = 'gpt-4o'): static
    {
        return $this->state(['provider' => 'openai', 'model' => $model]);
    }

    public function anthropic(string $model = '.claude-3-5-sonnet-20241022'): static
    {
        return $this->state(['provider' => 'anthropic', 'model' => $model]);
    }
}
