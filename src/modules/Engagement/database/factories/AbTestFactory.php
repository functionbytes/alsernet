<?php

namespace Modules\Engagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Engagement\Models\AbTest;

class AbTestFactory extends Factory
{
    protected $model = AbTest::class;

    public function definition(): array
    {
        return [
            'inbox_id' => 1,
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->sentence(),
            'status' => $this->faker->randomElement([
                AbTest::STATUS_DRAFT,
                AbTest::STATUS_RUNNING,
                AbTest::STATUS_PAUSED,
                AbTest::STATUS_COMPLETED,
            ]),
            'sample_size' => $this->faker->numberBetween(100, 10000),
            'started_at' => null,
            'ended_at' => null,
            'winner_variant_id' => null,
        ];
    }

    public function running(): static
    {
        return $this->state([
            'status' => AbTest::STATUS_RUNNING,
            'started_at' => now()->subDays(3),
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => AbTest::STATUS_COMPLETED,
            'started_at' => now()->subDays(14),
            'ended_at' => now()->subDays(7),
        ]);
    }
}
