<?php

namespace Modules\HelpdeskTickets\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskTickets\Models\TicketStatus;

class TicketStatusFactory extends Factory
{
    protected $model = TicketStatus::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Abierto', 'En proceso', 'Pendiente', 'Resuelto', 'Cerrado']).'-'.fake()->numberBetween(1, 99),
            'slug' => fake()->unique()->slug(2),
            'color' => fake()->hexColor(),
            'description' => fake()->optional()->sentence(),
            'order' => null,
            'is_default' => false,
            'is_system' => false,
            'is_open' => true,
            'stops_sla_timer' => false,
            'active' => true,
        ];
    }

    public function open(): static
    {
        return $this->state(['is_open' => true, 'stops_sla_timer' => false]);
    }

    public function closed(): static
    {
        return $this->state(['is_open' => false, 'stops_sla_timer' => true]);
    }

    public function default(): static
    {
        return $this->state(['is_default' => true, 'is_open' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }
}
