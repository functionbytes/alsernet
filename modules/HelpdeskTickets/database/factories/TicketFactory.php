<?php

namespace Modules\HelpdeskTickets\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskTickets\Models\Ticket;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'subject' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'priority' => fake()->randomElement(['low', 'normal', 'high', 'urgent']),
            'source' => fake()->randomElement(['email', 'web', 'phone', 'chat']),
            'is_archived' => false,
            'sla_first_response_breached' => false,
            'sla_next_response_breached' => false,
            'sla_resolution_breached' => false,
            'sla_paused_duration_minutes' => 0,
            'escalation_count' => 0,
        ];
    }

    public function urgent(): static
    {
        return $this->state(['priority' => 'urgent']);
    }

    public function archived(): static
    {
        return $this->state(['is_archived' => true]);
    }

    public function resolved(): static
    {
        return $this->state(['resolved_at' => now()]);
    }

    public function closed(): static
    {
        return $this->state(['closed_at' => now()]);
    }
}
