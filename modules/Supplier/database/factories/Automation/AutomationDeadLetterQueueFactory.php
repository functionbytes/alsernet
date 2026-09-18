<?php

namespace Modules\Supplier\Database\Factories\Automation;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Supplier\Models\Automation\AutomationDeadLetterQueue;
use Modules\Supplier\Models\Automation\AutomationExecution;

/**
 * @extends Factory<AutomationDeadLetterQueue>
 */
class AutomationDeadLetterQueueFactory extends Factory
{
    protected $model = AutomationDeadLetterQueue::class;

    public function definition(): array
    {
        return [
            'execution_id' => AutomationExecution::factory(),
            'failure_reason' => fake()->randomElement(['max_retries', 'invalid_config', 'source_gone', 'timeout', 'manual']),
            'error_details' => ['message' => fake()->sentence(), 'code' => fake()->numberBetween(400, 599)],
            'original_payload' => ['supplier_id' => fake()->numberBetween(1, 100)],
            'requires_action' => fake()->randomElement(['retry', 'fix_config', 'contact_supplier', 'skip', 'none']),
            'resolution_notes' => null,
            'resolved_by' => null,
            'resolved_at' => null,
        ];
    }

    public function resolved(): static
    {
        return $this->state([
            'resolved_at' => now(),
            'resolution_notes' => fake()->sentence(),
        ]);
    }

    public function needsIntervention(): static
    {
        return $this->state([
            'requires_action' => 'fix_config',
            'resolved_at' => null,
        ]);
    }
}
