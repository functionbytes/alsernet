<?php

namespace Modules\Supplier\Database\Factories\Ai;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Supplier\Models\Ai\AiBudget;

class AiBudgetFactory extends Factory
{
    protected $model = AiBudget::class;

    public function definition(): array
    {
        return [
            'provider' => fake()->unique()->randomElement(['openai', 'anthropic', 'gemini', 'cohere']),
            'monthly_limit' => fake()->randomFloat(2, 50, 1000),
            'daily_limit' => fake()->optional()->randomFloat(2, 10, 100),
            'alert_threshold_pct' => fake()->randomFloat(2, 50, 95),
            'is_active' => true,
            'block_on_exceed' => fake()->boolean(20),
            'notify_emails' => fake()->optional()->passthrough([fake()->safeEmail()]),
        ];
    }

    public function exceeded(): static
    {
        return $this->state([
            'monthly_limit' => 0.01,
            'alert_threshold_pct' => 0.01,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
