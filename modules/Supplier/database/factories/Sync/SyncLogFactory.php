<?php

namespace Modules\Supplier\Database\Factories\Sync;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Supplier\Models\Sync\SyncLog;

class SyncLogFactory extends Factory
{
    protected $model = SyncLog::class;

    public function definition(): array
    {
        return [
            'uid' => Str::ulid(),
            'batch_id' => null,
            'status_id' => null,
            'entity_type' => $this->faker->randomElement(['product', 'category', 'price', 'provider']),
            'entity_id' => $this->faker->numberBetween(1, 1000),
            'erp_id' => $this->faker->numberBetween(1, 100000),
            'action' => $this->faker->randomElement(['create', 'update', 'sync', 'skip']),
            'result' => $this->faker->randomElement(['success', 'failed', 'skipped']),
            'message' => $this->faker->sentence(),
            'data_before' => null,
            'data_after' => null,
            'changes' => null,
            'error_code' => null,
            'error_message' => null,
            'retry_count' => 0,
            'duration_ms' => $this->faker->numberBetween(0, 5000),
            'triggered_by' => 'sync_job',
            'metadata' => null,
            'synced_at' => now(),
        ];
    }
}
