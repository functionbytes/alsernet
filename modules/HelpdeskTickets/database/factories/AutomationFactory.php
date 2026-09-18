<?php

namespace Modules\HelpdeskTickets\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskTickets\Models\Automation;

class AutomationFactory extends Factory
{
    protected $model = Automation::class;

    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'trigger_event' => fake()->randomElement(array_keys(Automation::$triggerEvents)),
            'conditions' => [
                ['field' => 'priority', 'operator' => 'equals', 'value' => 'urgent'],
            ],
            'actions' => [
                ['type' => 'assign_group', 'value' => 1],
            ],
            'order' => fake()->numberBetween(1, 20),
            'is_active' => true,
            'run_count' => 0,
            'last_run_at' => null,
            'user_id' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function withRunHistory(): static
    {
        return $this->state([
            'run_count' => fake()->numberBetween(1, 500),
            'last_run_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }
}
