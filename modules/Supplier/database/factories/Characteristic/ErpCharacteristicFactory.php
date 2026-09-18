<?php

namespace Modules\Supplier\Database\Factories\Characteristic;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Supplier\Models\Characteristic\ErpCharacteristic;

/**
 * @extends Factory<ErpCharacteristic>
 */
class ErpCharacteristicFactory extends Factory
{
    protected $model = ErpCharacteristic::class;

    public function definition(): array
    {
        return [
            'erp_id' => fake()->unique()->numberBetween(1000, 999999),
            'nombre' => fake()->words(2, true),
            'estado' => true,
            'orden' => fake()->numberBetween(1, 100),
            'last_sync_at' => now(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['estado' => false]);
    }
}
