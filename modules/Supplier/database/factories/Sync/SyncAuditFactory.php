<?php

namespace Modules\Supplier\Database\Factories\Sync;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Supplier\Models\Sync\SyncAudit;

class SyncAuditFactory extends Factory
{
    protected $model = SyncAudit::class;

    public function definition(): array
    {
        $synced = $this->faker->numberBetween(0, 1000);
        $failed = $this->faker->numberBetween(0, 50);
        $skipped = $this->faker->numberBetween(0, 100);

        return [
            'uid' => Str::ulid(),
            'entity_type' => $this->faker->randomElement(['product', 'category', 'price', 'provider']),
            'sync_direction' => $this->faker->randomElement(['supplier_to_erp', 'erp_to_supplier']),
            'records_synced' => $synced,
            'records_failed' => $failed,
            'records_skipped' => $skipped,
            'elapsed_seconds' => $this->faker->randomFloat(3, 0.1, 600),
            'memory_mb' => $this->faker->randomFloat(2, 16, 512),
            'peak_memory_mb' => $this->faker->randomFloat(2, 16, 512),
            'metadata' => null,
            'cycle_number' => $this->faker->numberBetween(1, 100),
            'triggered_by' => $this->faker->randomElement(['manual', 'scheduled', 'webhook', 'api']),
            'synced_at' => now(),
        ];
    }
}
