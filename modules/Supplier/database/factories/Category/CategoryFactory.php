<?php

namespace Modules\Supplier\Database\Factories\Category;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Supplier\Models\Category\Category;
use Modules\Supplier\Models\Category\Sport;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'erp_id' => fake()->unique()->randomNumber(5),
            'sport_id' => Sport::factory(),
            'erp_sport_id' => fake()->optional()->randomNumber(5),
            'name' => fake()->words(2, true),
            'short_name' => fake()->optional()->word(),
            'available' => true,
            'erp_created_at' => now()->subMonths(6),
            'erp_updated_at' => now()->subDays(7),
            'last_sync_at' => now()->subDays(1),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['available' => false]);
    }

    public function withoutSport(): static
    {
        return $this->state(['sport_id' => null, 'erp_sport_id' => null]);
    }
}
