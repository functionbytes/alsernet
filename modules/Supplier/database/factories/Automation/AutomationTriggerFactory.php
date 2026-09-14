<?php

namespace Modules\Supplier\Database\Factories\Automation;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Supplier\Models\Automation\AutomationTrigger;
use Modules\Supplier\Models\Automation\AutomationWorkflow;

/**
 * @extends Factory<AutomationTrigger>
 */
class AutomationTriggerFactory extends Factory
{
    protected $model = AutomationTrigger::class;

    public function definition(): array
    {
        $type = fake()->randomElement([
            'schedule', 'webhook', 'file_upload', 'api_call', 'source_change', 'manual', 'dependent',
        ]);

        return [
            'uid' => (string) Str::ulid(),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'trigger_type' => $type,
            'trigger_config' => $this->configForType($type),
            'workflow_id' => AutomationWorkflow::factory(),
            'source_filter' => fake()->optional()->randomElement([['source_type' => 'website'], ['supplier_id' => 1]]),
            'execution_context' => ['locale' => 'es'],
            'trigger_conditions' => null,
            'is_enabled' => true,
            'last_triggered_at' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
            'total_triggers' => fake()->numberBetween(0, 50),
        ];
    }

    public function schedule(): static
    {
        return $this->state([
            'trigger_type' => 'schedule',
            'trigger_config' => ['cron_expression' => '0 * * * *'],
        ]);
    }

    public function webhook(): static
    {
        return $this->state([
            'trigger_type' => 'webhook',
            'trigger_config' => ['webhook_url' => fake()->url()],
        ]);
    }

    public function disabled(): static
    {
        return $this->state(['is_enabled' => false]);
    }

    /**
     * @return array<string, mixed>
     */
    private function configForType(string $type): array
    {
        return match ($type) {
            'schedule' => ['cron_expression' => '0 * * * *'],
            'webhook' => ['webhook_url' => fake()->url()],
            'dependent' => ['depends_on_workflow' => 1, 'depends_on_status' => 'completed'],
            default => [],
        };
    }
}
