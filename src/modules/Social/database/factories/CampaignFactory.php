<?php

namespace Modules\Social\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Social\Models\Campaign;

class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        return [
            'account_id' => 1,
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'color' => fake()->hexColor(),
            'start_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'end_date' => fake()->optional()->dateTimeBetween('now', '+3 months'),
            'is_active' => fake()->boolean(80),
            'created_by' => 1,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
