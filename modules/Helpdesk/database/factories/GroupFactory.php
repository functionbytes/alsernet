<?php

namespace Modules\Helpdesk\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Helpdesk\Models\Group;

class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Soporte', 'Ventas', 'Técnico', 'Atención al cliente', 'Escalaciones']).'-'.fake()->numberBetween(1, 99),
            'assignment_mode' => fake()->randomElement(['manual', 'round_robin', 'load_balanced']),
            'default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(['default' => true]);
    }

    public function roundRobin(): static
    {
        return $this->state(['assignment_mode' => 'round_robin']);
    }

    public function loadBalanced(): static
    {
        return $this->state(['assignment_mode' => 'load_balanced']);
    }
}
