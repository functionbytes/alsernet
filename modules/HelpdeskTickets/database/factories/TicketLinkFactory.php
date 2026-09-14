<?php

namespace Modules\HelpdeskTickets\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskTickets\Models\TicketLink;

class TicketLinkFactory extends Factory
{
    protected $model = TicketLink::class;

    public function definition(): array
    {
        return [
            'ticket_id' => null, // set via relationship
            'linked_ticket_id' => null, // set via relationship
            'link_type' => fake()->randomElement(['related', 'duplicate', 'blocks', 'blocked_by']),
            'created_by' => null,
        ];
    }

    public function related(): static
    {
        return $this->state(['link_type' => 'related']);
    }

    public function duplicate(): static
    {
        return $this->state(['link_type' => 'duplicate']);
    }
}
