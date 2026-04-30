<?php

namespace Modules\Chat\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Customers\Customer;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone_number' => fake()->phoneNumber(),
            'avatar_url' => null,
            'identifier' => fake()->unique()->uuid(),
            'custom_attributes' => [],
            'additional_attributes' => [],
            'last_activity_at' => now(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_activity_at' => now()->subMinutes(10),
        ]);
    }

    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_banned' => true,
            'banned_at' => now(),
        ]);
    }
}
