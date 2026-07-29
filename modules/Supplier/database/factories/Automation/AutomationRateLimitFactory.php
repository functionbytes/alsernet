<?php

namespace Modules\Supplier\Database\Factories\Automation;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Supplier\Models\Automation\AutomationRateLimit;
use Modules\Supplier\Models\Source\Source;

/**
 * @extends Factory<AutomationRateLimit>
 */
class AutomationRateLimitFactory extends Factory
{
    protected $model = AutomationRateLimit::class;

    public function definition(): array
    {
        $maxRequests = fake()->numberBetween(10, 1000);

        return [
            'limitable_type' => Source::class,
            'limitable_id' => Source::factory(),
            'window_type' => fake()->randomElement(['minute', 'hour', 'day']),
            'max_requests' => $maxRequests,
            'current_count' => fake()->numberBetween(0, $maxRequests),
            'window_start' => now()->subMinutes(fake()->numberBetween(0, 60)),
            'blocked_until' => null,
            'updated_at' => now(),
        ];
    }

    public function blocked(): static
    {
        return $this->state(fn (array $attributes): array => [
            'current_count' => $attributes['max_requests'],
            'blocked_until' => now()->addHour(),
        ]);
    }

    public function perMinute(): static
    {
        return $this->state(['window_type' => 'minute']);
    }
}
