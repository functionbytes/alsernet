<?php

namespace Modules\Engagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Engagement\Models\AbTest;
use Modules\Engagement\Models\AbTestVariant;

class AbTestVariantFactory extends Factory
{
    protected $model = AbTestVariant::class;

    public function definition(): array
    {
        return [
            'ab_test_id' => AbTest::factory(),
            'name' => 'Variante '.$this->faker->word(),
            'config' => ['message' => $this->faker->sentence()],
            'weight' => $this->faker->numberBetween(1, 100),
            'impressions' => $this->faker->numberBetween(0, 5000),
            'conversions' => $this->faker->numberBetween(0, 500),
        ];
    }

    public function control(): static
    {
        return $this->state([
            'name' => 'Control',
            'weight' => 50,
        ]);
    }
}
