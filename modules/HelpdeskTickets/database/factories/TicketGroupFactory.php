<?php

namespace Modules\HelpdeskTickets\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskTickets\Models\TicketGroup;

class TicketGroupFactory extends Factory
{
    protected $model = TicketGroup::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Soporte nivel 1', 'Soporte nivel 2', 'Ventas', 'Facturación', 'Técnicos']).'-'.fake()->numberBetween(1, 99),
            'description' => fake()->optional()->sentence(),
            'assignment_mode' => fake()->randomElement(['manual', 'round_robin', 'load_balanced']),
            'is_default' => false,
            'is_active' => true,
            'order' => null,
        ];
    }

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function roundRobin(): static
    {
        return $this->state(['assignment_mode' => 'round_robin']);
    }
}
