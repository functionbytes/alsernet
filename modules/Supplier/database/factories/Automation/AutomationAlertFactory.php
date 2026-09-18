<?php

namespace Modules\Supplier\Database\Factories\Automation;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Supplier\Models\Automation\AutomationAlert;

/**
 * @extends Factory<AutomationAlert>
 */
class AutomationAlertFactory extends Factory
{
    protected $model = AutomationAlert::class;

    public function definition(): array
    {
        return [
            'uid' => (string) Str::ulid(),
            'alert_type' => fake()->randomElement([
                'server_unreachable', 'workflow_disabled', 'high_failure_rate', 'rate_limit_exceeded',
                'credential_expiring', 'credential_expired', 'execution_timeout', 'dead_letter_threshold',
                'queue_backlog', 'scraping_blocked',
            ]),
            'severity' => fake()->randomElement(['info', 'warning', 'error', 'critical']),
            'title' => fake()->sentence(4),
            'message' => fake()->paragraph(),
            'context' => ['detail' => fake()->sentence()],
            'related_type' => null,
            'related_id' => null,
            'acknowledged_by' => null,
            'acknowledged_at' => null,
            'resolved_at' => null,
        ];
    }

    public function critical(): static
    {
        return $this->state(['severity' => 'critical']);
    }

    public function acknowledged(): static
    {
        return $this->state(['acknowledged_at' => now()]);
    }

    public function resolved(): static
    {
        return $this->state([
            'acknowledged_at' => now()->subMinutes(10),
            'resolved_at' => now(),
        ]);
    }
}
