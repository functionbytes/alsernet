<?php

namespace Modules\Supplier\Database\Factories\Sync;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Supplier\Models\Supplier\Supplier;
use Modules\Supplier\Models\Sync\SyncBatch;

class SyncBatchFactory extends Factory
{
    protected $model = SyncBatch::class;

    public function definition(): array
    {
        return [
            'uid' => (string) Str::ulid(),
            'supplier_id' => Supplier::factory(),
            'batch_name' => fake()->words(3, true),
            'sync_type' => fake()->randomElement(['providers', 'products', 'categories', 'models', 'prices']),
            'status' => fake()->randomElement(['pending', 'running', 'completed', 'failed', 'cancelled']),
            'priority' => fake()->randomElement(['low', 'normal', 'high', 'urgent']),
            'batch_size' => fake()->numberBetween(50, 500),
            'total_batches' => fake()->numberBetween(1, 10),
            'processed_batches' => fake()->numberBetween(0, 5),
            'total_items' => fake()->numberBetween(0, 1000),
            'processed_items' => fake()->numberBetween(0, 500),
            'failed_items' => fake()->numberBetween(0, 50),
            'retry_attempt' => fake()->numberBetween(0, 2),
            'max_retries' => 3,
            'started_at' => now()->subMinutes(fake()->numberBetween(1, 60)),
            'completed_at' => fake()->optional()->dateTimeBetween('-1 hour', 'now'),
            'last_retry_at' => null,
            'duration_seconds' => fake()->optional()->randomFloat(2, 1, 300),
            'triggered_by' => fake()->randomElement(['manual', 'schedule', 'webhook', 'api']),
            'trigger_details' => fake()->optional()->sentence(),
            'filter_criteria' => null,
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
}
