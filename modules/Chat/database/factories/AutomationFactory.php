<?php

namespace Modules\Chat\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Automations\Automation;

class AutomationFactory extends Factory
{
    protected $model = Automation::class;

    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'event_name' => 'conversation_created',
            'conditions' => [],
            'actions' => [],
            'active' => true,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    public function withConditions(array $conditions): static
    {
        return $this->state(fn (array $attributes) => [
            'conditions' => $conditions,
        ]);
    }

    public function withActions(array $actions): static
    {
        return $this->state(fn (array $attributes) => [
            'actions' => $actions,
        ]);
    }

    public function forEvent(string $eventName): static
    {
        return $this->state(fn (array $attributes) => [
            'event_name' => $eventName,
        ]);
    }
}
