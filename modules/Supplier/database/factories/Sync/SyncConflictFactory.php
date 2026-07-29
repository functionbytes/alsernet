<?php

namespace Modules\Supplier\Database\Factories\Sync;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Supplier\Models\Sync\SyncConflict;

class SyncConflictFactory extends Factory
{
    protected $model = SyncConflict::class;

    public function definition(): array
    {
        return [
            'entity_type' => $this->faker->randomElement(['product', 'category', 'price']),
            'entity_id' => $this->faker->numberBetween(1, 1000),
            'erp_id' => $this->faker->numberBetween(1, 100000),
            'resolution_strategy' => $this->faker->randomElement(['erp_wins', 'supplier_wins', 'manual']),
            'local_data' => ['name' => 'Local value', 'cost' => 10.5],
            'erp_data' => ['name' => 'ERP value', 'cost' => 11.0],
            'resolved_data' => null,
            'changed_fields' => ['name', 'cost'],
            'conflict_detected_at' => now(),
            'resolved_at' => null,
            'resolved_by_user_id' => null,
            'resolved_by_ip' => null,
            'notes' => null,
        ];
    }

    public function resolved(): self
    {
        return $this->state(fn () => [
            'resolved_at' => now(),
            'resolved_data' => ['name' => 'Resolved', 'cost' => 11.0],
        ]);
    }
}
