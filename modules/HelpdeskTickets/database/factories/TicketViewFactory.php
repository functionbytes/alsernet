<?php

namespace Modules\HelpdeskTickets\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskTickets\Models\TicketView;

class TicketViewFactory extends Factory
{
    protected $model = TicketView::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'ticket_id' => null,
            'name' => fake()->randomElement(['Mis tickets', 'Sin asignar', 'Urgentes', 'Esta semana', 'Abiertos']).'-'.fake()->numberBetween(1, 99),
            'description' => fake()->optional()->sentence(),
            'filters' => [],
            'sort_by' => fake()->randomElement(['created_at', 'updated_at', 'priority', null]),
            'sort_direction' => fake()->randomElement(['asc', 'desc']),
            'is_default' => false,
            'is_shared' => false,
            'is_system' => false,
            'order' => null,
        ];
    }

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }

    public function shared(): static
    {
        return $this->state(['is_shared' => true]);
    }

    public function withFilters(): static
    {
        return $this->state([
            'filters' => [
                'is_open' => true,
                'priority' => 'high',
            ],
        ]);
    }
}
