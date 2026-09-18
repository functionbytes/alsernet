<?php

namespace Modules\Supplier\Database\Factories\Category;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Supplier\Models\Category\Sport;

class SportFactory extends Factory
{
    protected $model = Sport::class;

    public function definition(): array
    {
        return [
            'erp_id' => fake()->unique()->randomNumber(5),
            'name' => fake()->words(2, true),
            'short_name' => fake()->optional()->word(),
            'available' => true,
            'last_sync_at' => now()->subDays(1),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['available' => false]);
    }
}
