<?php

namespace Modules\Supplier\Database\Factories\Automation;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Supplier\Models\Automation\AutomationChain;
use Modules\Supplier\Models\Automation\AutomationChainExecution;

/**
 * @extends Factory<AutomationChainExecution>
 */
class AutomationChainExecutionFactory extends Factory
{
    protected $model = AutomationChainExecution::class;

    public function definition(): array
    {
        return [
            'uid' => (string) Str::ulid(),
            'chain_id' => AutomationChain::factory(),
            'status' => 'pending',
            'current_stage' => null,
            'completed_stages' => [],
            'failed_stages' => [],
            'execution_context' => ['locale' => 'es'],
            'stage_results' => [],
            'started_at' => null,
            'completed_at' => null,
            'pending_approval_stage' => null,
            'approval_requested_at' => null,
            'approved_by' => null,
            'approved_at' => null,
            'triggered_by' => fake()->randomElement(['manual', 'schedule', 'api', 'trigger']),
            'triggered_by_user' => null,
        ];
    }

    public function running(): static
    {
        return $this->state([
            'status' => 'running',
            'started_at' => now()->subMinutes(fake()->numberBetween(1, 30)),
            'current_stage' => 'extract',
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => 'completed',
            'started_at' => now()->subHour(),
            'completed_at' => now(),
            'completed_stages' => ['extract', 'generate'],
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => 'failed',
            'started_at' => now()->subHour(),
            'completed_at' => now(),
            'failed_stages' => ['generate'],
        ]);
    }

    public function waitingApproval(): static
    {
        return $this->state([
            'status' => 'waiting_approval',
            'started_at' => now()->subMinutes(10),
            'pending_approval_stage' => 'publish',
            'approval_requested_at' => now()->subMinutes(5),
        ]);
    }
}
