<?php

namespace Modules\Supplier\Database\Factories\Sync;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Supplier\Models\Sync\SyncSchedule;

/**
 * @extends Factory<SyncSchedule>
 */
class SyncScheduleFactory extends Factory
{
    protected $model = SyncSchedule::class;

    public function definition(): array
    {
        return [
            'uid' => (string) Str::ulid(),
            'sync_type' => fake()->randomElement(['model', 'product', 'category', 'price']),
            'label' => fake()->words(3, true),
            'hour' => fake()->numberBetween(0, 23),
            'minute' => fake()->numberBetween(0, 59),
            'is_enabled' => true,
            'last_run_at' => fake()->optional()->dateTimeBetween('-1 week', 'now'),
            'last_run_status' => fake()->optional()->randomElement(['success', 'failed', 'running']),
            'last_batch_id' => null,
            'metadata' => null,
        ];
    }

    public function disabled(): static
    {
        return $this->state(['is_enabled' => false]);
    }

    public function ranSuccessfully(): static
    {
        return $this->state([
            'last_run_at' => now()->subHours(2),
            'last_run_status' => 'success',
        ]);
    }
}
