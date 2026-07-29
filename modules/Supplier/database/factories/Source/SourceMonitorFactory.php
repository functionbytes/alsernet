<?php

namespace Modules\Supplier\Database\Factories\Source;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Supplier\Models\Source\Source;
use Modules\Supplier\Models\Source\SourceMonitor;

/**
 * @extends Factory<SourceMonitor>
 */
class SourceMonitorFactory extends Factory
{
    protected $model = SourceMonitor::class;

    public function definition(): array
    {
        return [
            'uid' => (string) Str::ulid(),
            'source_id' => Source::factory(),
            'status' => fake()->randomElement(['healthy', 'degraded', 'unhealthy', 'unreachable', 'unknown']),
            'status_message' => fake()->optional()->sentence(),
            'status_code' => fake()->optional()->randomElement([200, 301, 404, 500, 503]),
            'uptime_percentage' => fake()->randomFloat(2, 80, 100),
            'avg_response_time_ms' => fake()->numberBetween(50, 3000),
            'last_successful_check_at' => now()->subMinutes(fake()->numberBetween(0, 120)),
            'last_failed_check_at' => fake()->optional()->dateTimeBetween('-1 week', 'now'),
            'consecutive_failures' => fake()->numberBetween(0, 5),
            'consecutive_successes' => fake()->numberBetween(0, 50),
            'structure_hash' => fake()->optional()->sha256(),
            'structure_changed_at' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
            'content_hash' => fake()->optional()->sha256(),
            'content_changed_at' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
            'check_interval_minutes' => fake()->randomElement([5, 15, 30, 60]),
            'health_check_url' => fake()->optional()->url(),
            'expected_content_selector' => fake()->optional()->randomElement(['.product-list', '#catalog', 'main']),
            'alert_on_failure' => true,
            'alert_on_structure_change' => true,
            'alert_sent_at' => null,
            'snooze_alerts_until' => null,
        ];
    }

    public function healthy(): static
    {
        return $this->state([
            'status' => 'healthy',
            'consecutive_failures' => 0,
            'uptime_percentage' => 100.00,
        ]);
    }

    public function unreachable(): static
    {
        return $this->state([
            'status' => 'unreachable',
            'consecutive_failures' => fake()->numberBetween(3, 20),
        ]);
    }
}
