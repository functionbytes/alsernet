<?php

namespace Modules\Supplier\Database\Factories\Sync;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Supplier\Models\Supplier\Supplier;
use Modules\Supplier\Models\Sync\SyncFailure;

class SyncFailureFactory extends Factory
{
    protected $model = SyncFailure::class;

    public function definition(): array
    {
        return [
            'uid' => (string) Str::ulid(),
            'batch_id' => null,
            'sync_type' => fake()->randomElement(['providers', 'products', 'categories', 'models', 'prices']),
            'supplier_id' => Supplier::factory(),
            'entity_id' => fake()->optional()->randomNumber(5),
            'erp_id' => fake()->optional()->randomNumber(5),
            'changed_data' => ['name' => fake()->word(), 'price' => fake()->randomFloat(2, 10, 100)],
            'context' => null,
            'error_message' => fake()->sentence(),
            'error_code' => fake()->optional()->bothify('ERR-####'),
            'retry_count' => fake()->numberBetween(0, 2),
            'max_retries' => 3,
            'failure_status' => fake()->randomElement(['pending', 'retrying', 'resolved', 'permanent']),
            'last_retry_at' => fake()->optional()->dateTimeBetween('-1 day', 'now'),
            'resolved_at' => null,
            'resolved_by_user_id' => null,
            'resolution_notes' => null,
        ];
    }

    public function resolved(): static
    {
        return $this->state([
            'failure_status' => 'resolved',
            'resolved_at' => now(),
        ]);
    }

    public function permanent(): static
    {
        return $this->state([
            'failure_status' => 'permanent',
            'retry_count' => 3,
        ]);
    }
}
