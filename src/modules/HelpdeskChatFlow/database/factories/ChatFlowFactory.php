<?php

namespace Modules\HelpdeskChatFlow\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\HelpdeskChatFlow\Models\ChatFlow;

class ChatFlowFactory extends Factory
{
    protected $model = ChatFlow::class;

    public function definition(): array
    {
        return [
            'uid' => Str::uuid(),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->sentence(),
            'inbox_id' => null,
            'trigger_type' => $this->faker->randomElement(ChatFlow::TRIGGER_TYPES),
            'trigger_conditions' => [],
            'nodes' => [
                ['id' => 'n1', 'type' => 'start', 'config' => []],
                ['id' => 'n2', 'type' => 'end', 'config' => []],
            ],
            'edges' => [
                ['source' => 'n1', 'target' => 'n2'],
            ],
            'status' => 'draft',
            'priority' => 0,
            'created_by' => null,
        ];
    }

    public function active(): static
    {
        return $this->state([
            'status' => 'active',
            'published_at' => now(),
        ]);
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft']);
    }

    public function archived(): static
    {
        return $this->state(['status' => 'archived']);
    }

    public function withTrigger(string $triggerType, array $conditions = []): static
    {
        return $this->state([
            'trigger_type' => $triggerType,
            'trigger_conditions' => $conditions,
        ]);
    }
}
