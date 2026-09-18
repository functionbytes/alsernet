<?php

namespace Modules\Supplier\Database\Factories\Automation;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Supplier\Models\Automation\AutomationHealthCheck;

/**
 * @extends Factory<AutomationHealthCheck>
 */
class AutomationHealthCheckFactory extends Factory
{
    protected $model = AutomationHealthCheck::class;

    public function definition(): array
    {
        return [
            'check_type' => fake()->randomElement(['server', 'workflow', 'webhook', 'credential']),
            'target_id' => (string) fake()->numberBetween(1, 100),
            'status' => fake()->randomElement(['healthy', 'degraded', 'unhealthy', 'unknown']),
            'response_time_ms' => fake()->numberBetween(10, 2000),
            'error_message' => null,
            'metadata' => ['region' => fake()->randomElement(['eu-west', 'us-east'])],
            'checked_at' => now()->subMinutes(fake()->numberBetween(0, 120)),
        ];
    }

    public function healthy(): static
    {
        return $this->state(['status' => 'healthy', 'error_message' => null]);
    }

    public function unhealthy(): static
    {
        return $this->state([
            'status' => 'unhealthy',
            'error_message' => fake()->sentence(),
        ]);
    }
}
