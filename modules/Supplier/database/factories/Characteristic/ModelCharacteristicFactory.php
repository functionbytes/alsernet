<?php

namespace Modules\Supplier\Database\Factories\Characteristic;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Supplier\Models\Characteristic\ErpCharacteristic;
use Modules\Supplier\Models\Characteristic\ModelCharacteristic;

/**
 * @extends Factory<ModelCharacteristic>
 */
class ModelCharacteristicFactory extends Factory
{
    protected $model = ModelCharacteristic::class;

    public function definition(): array
    {
        return [
            'erp_id' => fake()->unique()->numberBetween(1000, 999999),
            'erp_model_id' => fake()->numberBetween(1000, 999999),
            'characteristic_id' => ErpCharacteristic::factory(),
            'orden' => fake()->numberBetween(1, 100),
            'estado' => true,
            'last_sync_at' => now(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['estado' => false]);
    }
}
