<?php

namespace Modules\HelpdeskTickets\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskTickets\Models\TicketTimeEntry;

class TicketTimeEntryFactory extends Factory
{
    protected $model = TicketTimeEntry::class;

    public function definition(): array
    {
        return [
            'ticket_id' => null, // set via relationship
            'user_id' => 1,    // assumes at least one user; override as needed
            'description' => fake()->optional()->sentence(),
            'minutes' => fake()->numberBetween(5, 480),
            'logged_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /** Short 15-minute quick-log entry. */
    public function quick(): static
    {
        return $this->state(['minutes' => 15]);
    }

    /** Full work-day entry (8 hours). */
    public function fullDay(): static
    {
        return $this->state(['minutes' => 480]);
    }
}
