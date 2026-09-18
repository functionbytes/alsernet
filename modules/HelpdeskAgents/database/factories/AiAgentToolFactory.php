<?php

namespace Modules\HelpdeskAgents\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskAgents\Models\AiAgentTool;

class AiAgentToolFactory extends Factory
{
    protected $model = AiAgentTool::class;

    public function definition(): array
    {
        return [
            'ai_agent_id' => null,
            'name' => fake()->randomElement(['search_knowledge', 'create_ticket', 'fetch_order', 'send_email', 'check_status']).'-'.fake()->numberBetween(1, 99),
            'description' => fake()->sentence(),
            'type' => fake()->randomElement(['api', 'function', 'webhook', 'database']),
            'parameters' => [
                'query' => ['type' => 'string', 'required' => true, 'description' => 'Search query'],
            ],
            'implementation' => fake()->url(),
            'auth_config' => null,
            'requires_approval' => false,
            'usage_count' => 0,
            'last_used_at' => null,
            'is_active' => true,
            'metadata' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function requiresApproval(): static
    {
        return $this->state(['requires_approval' => true]);
    }

    public function withUsage(): static
    {
        return $this->state([
            'usage_count' => fake()->numberBetween(10, 500),
            'last_used_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }
}
