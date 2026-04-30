<?php

namespace Modules\Chat\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Sla\SlaPolicy;

class SlaPolicyFactory extends Factory
{
    protected $model = SlaPolicy::class;

    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'first_response_time_minutes' => 60,
            'resolution_time_minutes' => 240,
            'is_active' => true,
            'business_hours_only' => false,
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

    public function businessHours(): static
    {
        return $this->state(fn (array $attributes) => [
            'business_hours_only' => true,
        ]);
    }

    public function twentyFourSeven(): static
    {
        return $this->state(fn (array $attributes) => [
            'business_hours_only' => false,
        ]);
    }
}
