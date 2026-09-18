<?php

namespace Modules\Helpdesk\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Helpdesk\Models\Macro;

class MacroFactory extends Factory
{
    protected $model = Macro::class;

    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'actions' => [
                ['type' => fake()->randomElement(array_keys(Macro::ACTION_TYPES)), 'value' => ''],
            ],
            'is_shared' => true,
            'user_id' => null,
            'usage_count' => 0,
            'last_used_at' => null,
            'is_active' => true,
        ];
    }

    public function personal(int $userId): static
    {
        return $this->state([
            'is_shared' => false,
            'user_id' => $userId,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function withUsage(): static
    {
        return $this->state([
            'usage_count' => fake()->numberBetween(1, 200),
            'last_used_at' => fake()->dateTimeBetween('-60 days', 'now'),
        ]);
    }
}
