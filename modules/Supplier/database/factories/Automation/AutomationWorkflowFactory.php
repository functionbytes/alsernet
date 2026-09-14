<?php

namespace Modules\Supplier\Database\Factories\Automation;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Supplier\Models\Automation\AutomationWorkflow;

class AutomationWorkflowFactory extends Factory
{
    protected $model = AutomationWorkflow::class;

    public function definition(): array
    {
        $total = fake()->numberBetween(0, 100);
        $successful = fake()->numberBetween(0, $total);

        return [
            'uid' => (string) Str::ulid(),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->paragraph(),
            'workflow_type' => fake()->randomElement([
                'extraction', 'scraping', 'ftp_sync', 'content_generation',
                'validation', 'publication', 'monitoring',
            ]),
            'external_workflow_id' => fake()->optional()->uuid(),
            'webhook_url' => fake()->optional()->url(),
            'callback_url' => fake()->optional()->url(),
            'workflow_config' => null,
            'default_variables' => null,
            'timeout_seconds' => 300,
            'max_retries' => 3,
            'priority' => fake()->numberBetween(1, 10),
            'is_active' => true,
            'last_executed_at' => fake()->optional()->dateTimeBetween('-1 week', 'now'),
            'total_executions' => $total,
            'successful_executions' => $successful,
            'failed_executions' => $total - $successful,
            'created_by' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function ofType(string $type): static
    {
        return $this->state(['workflow_type' => $type]);
    }
}
