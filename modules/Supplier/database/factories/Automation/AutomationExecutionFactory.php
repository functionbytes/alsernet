<?php

namespace Modules\Supplier\Database\Factories\Automation;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Supplier\Models\Automation\AutomationExecution;
use Modules\Supplier\Models\Automation\AutomationWorkflow;

class AutomationExecutionFactory extends Factory
{
    protected $model = AutomationExecution::class;

    public function definition(): array
    {
        return [
            'workflow_id' => AutomationWorkflow::factory(),
            'supplier_id' => null,
            'source_id' => null,
            'external_execution_id' => 'exec_'.$this->faker->uuid(),
            'status' => $this->faker->randomElement(['pending', 'running', 'completed', 'failed']),
            'trigger_type' => $this->faker->randomElement(['manual', 'scheduled', 'webhook', 'event']),
            'input_payload' => ['key' => 'value'],
            'output_data' => null,
            'execution_context' => null,
            'error_details' => null,
            'duration_ms' => $this->faker->numberBetween(0, 60000),
            'retry_count' => 0,
            'items_processed' => $this->faker->numberBetween(0, 1000),
            'items_succeeded' => $this->faker->numberBetween(0, 1000),
            'items_failed' => $this->faker->numberBetween(0, 50),
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
            'triggered_by' => null,
        ];
    }

    public function failed(): self
    {
        return $this->state(fn () => [
            'status' => 'failed',
            'error_details' => ['message' => 'Mock error'],
        ]);
    }
}
