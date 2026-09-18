<?php

namespace Modules\Supplier\Database\Factories\Characteristic;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Supplier\Models\Characteristic\ErpCharacteristic;
use Modules\Supplier\Models\Characteristic\ErpCharacteristicValue;

/**
 * @extends Factory<ErpCharacteristicValue>
 */
class ErpCharacteristicValueFactory extends Factory
{
    protected $model = ErpCharacteristicValue::class;

    public function definition(): array
    {
        return [
            'erp_id' => fake()->unique()->numberBetween(1000, 999999),
            'characteristic_id' => ErpCharacteristic::factory(),
            'nombre' => fake()->words(2, true),
            'estado' => true,
            'last_sync_at' => now(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['estado' => false]);
    }
}
