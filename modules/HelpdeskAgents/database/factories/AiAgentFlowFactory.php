<?php

namespace Modules\HelpdeskAgents\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskAgents\Models\AiAgentFlow;

class AiAgentFlowFactory extends Factory
{
    protected $model = AiAgentFlow::class;

    public function definition(): array
    {
        return [
            'ai_agent_id' => null,
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'trigger' => fake()->randomElement(['message', 'keyword', 'event', 'schedule', 'manual']),
            'status' => 'draft',
            'nodes' => [],
            'edges' => [],
            'metadata' => null,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state([
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft', 'published_at' => null]);
    }

    public function withNodes(): static
    {
        return $this->state([
            'nodes' => [
                ['id' => 'start', 'type' => 'start', 'data' => []],
                ['id' => 'reply', 'type' => 'message', 'data' => ['text' => 'Hola, ¿en qué puedo ayudarte?']],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 'start', 'target' => 'reply'],
            ],
        ]);
    }
}
