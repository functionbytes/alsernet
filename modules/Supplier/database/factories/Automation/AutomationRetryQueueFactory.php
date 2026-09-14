<?php

namespace Modules\Supplier\Database\Factories\Automation;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Supplier\Models\Automation\AutomationExecution;
use Modules\Supplier\Models\Automation\AutomationRetryQueue;

/**
 * @extends Factory<AutomationRetryQueue>
 */
class AutomationRetryQueueFactory extends Factory
{
    protected $model = AutomationRetryQueue::class;

    public function definition(): array
    {
        return [
            'execution_id' => AutomationExecution::factory(),
            'attempt_number' => 1,
            'max_attempts' => 3,
            'retry_at' => now()->addMinutes(fake()->numberBetween(1, 60)),
            'retry_strategy' => fake()->randomElement(['immediate', 'linear', 'exponential']),
            'last_error' => fake()->optional()->sentence(),
            'status' => 'pending',
        ];
    }

    public function exhausted(): static
    {
        return $this->state([
            'attempt_number' => 3,
            'max_attempts' => 3,
            'status' => 'exhausted',
        ]);
    }

    public function ready(): static
    {
        return $this->state([
            'status' => 'pending',
            'retry_at' => now()->subMinutes(5),
        ]);
    }
}
