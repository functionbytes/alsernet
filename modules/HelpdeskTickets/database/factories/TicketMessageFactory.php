<?php

namespace Modules\HelpdeskTickets\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskTickets\Models\TicketMessage;

class TicketMessageFactory extends Factory
{
    protected $model = TicketMessage::class;

    public function definition(): array
    {
        $message = fake()->paragraph(fake()->numberBetween(1, 4));

        return [
            'ticket_id' => null, // set via relationship
            'user_id' => null,
            'is_internal' => false,
            'message' => $message,
            'message_html' => '<p>'.nl2br(e($message)).'</p>',
        ];
    }

    /**
     * Internal (private) note visible only to agents.
     */
    public function internal(): static
    {
        return $this->state(['is_internal' => true]);
    }

    /**
     * Public reply visible to the customer.
     */
    public function public(): static
    {
        return $this->state(['is_internal' => false]);
    }
}
