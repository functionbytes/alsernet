<?php

namespace Modules\Supplier\Database\Factories\Sync;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Supplier\Models\Supplier\Supplier;
use Modules\Supplier\Models\Sync\SyncStatus;

class SyncStatusFactory extends Factory
{
    protected $model = SyncStatus::class;

    public function definition(): array
    {
        return [
            'uid' => (string) Str::ulid(),
            'supplier_id' => Supplier::factory(),
            'batch_id' => null,
            'sync_type' => fake()->randomElement(['providers', 'products', 'categories', 'models', 'prices']),
            'sync_scope' => fake()->randomElement(['all', 'delta', 'specific']),
            'status' => fake()->randomElement(['pending', 'running', 'completed', 'failed', 'cancelled']),
            'total_items' => fake()->numberBetween(0, 1000),
            'synced_items' => fake()->numberBetween(0, 500),
            'failed_items' => fake()->numberBetween(0, 50),
            'skipped_items' => fake()->numberBetween(0, 100),
            'retry_count' => fake()->numberBetween(0, 3),
            'started_at' => now()->subMinutes(fake()->numberBetween(1, 60)),
            'completed_at' => fake()->optional()->dateTimeBetween('-1 hour', 'now'),
            'elapsed_seconds' => fake()->optional()->randomFloat(2, 1, 300),
            'memory_used_mb' => fake()->optional()->randomFloat(2, 10, 512),
            'triggered_by' => fake()->randomElement(['manual', 'schedule', 'webhook', 'api']),
            'notes' => fake()->optional()->sentence(),
            'metadata' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => 'failed',
            'failed_items' => fake()->numberBetween(1, 50),
        ]);
    }

    public function running(): static
    {
        return $this->state([
            'status' => 'running',
            'started_at' => now(),
            'completed_at' => null,
        ]);
    }
}
