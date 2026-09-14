<?php

namespace Modules\Supplier\Database\Factories\Automation;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Supplier\Models\Automation\AutomationWorkflow;
use Modules\Supplier\Models\Automation\AutomationWorkflowVersion;

/**
 * @extends Factory<AutomationWorkflowVersion>
 */
class AutomationWorkflowVersionFactory extends Factory
{
    protected $model = AutomationWorkflowVersion::class;

    public function definition(): array
    {
        return [
            'workflow_id' => AutomationWorkflow::factory(),
            'version' => fake()->numberBetween(1, 10),
            'workflow_json' => [
                'nodes' => [
                    ['id' => 'start', 'type' => 'trigger'],
                    ['id' => 'extract', 'type' => 'action'],
                ],
                'edges' => [['from' => 'start', 'to' => 'extract']],
            ],
            'changelog' => fake()->optional()->sentence(),
            'is_active' => false,
            'activated_at' => null,
            'created_by' => User::factory(),
        ];
    }

    public function active(): static
    {
        return $this->state([
            'is_active' => true,
            'activated_at' => now(),
        ]);
    }

    public function firstVersion(): static
    {
        return $this->state(['version' => 1]);
    }
}
