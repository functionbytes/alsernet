<?php

namespace Modules\Chat\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Teams\Team;

class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'allow_auto_assign' => true,
        ];
    }

    public function allowAutoAssign(): static
    {
        return $this->state(fn (array $attributes) => [
            'allow_auto_assign' => true,
        ]);
    }

    public function noAutoAssign(): static
    {
        return $this->state(fn (array $attributes) => [
            'allow_auto_assign' => false,
        ]);
    }
}
