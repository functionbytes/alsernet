<?php

namespace Modules\HelpdeskTickets\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskTickets\Models\TicketNote;

class TicketNoteFactory extends Factory
{
    protected $model = TicketNote::class;

    public function definition(): array
    {
        return [
            'ticket_id' => null, // set via relationship
            'user_id' => 1,    // assumes at least one user; override as needed
            'title' => fake()->optional()->sentence(3),
            'body' => fake()->paragraph(),
            'is_pinned' => false,
            'color' => fake()->randomElement(['yellow', 'blue', 'green', 'red', 'purple', 'orange']),
        ];
    }

    public function pinned(): static
    {
        return $this->state(['is_pinned' => true]);
    }

    public function yellow(): static
    {
        return $this->state(['color' => 'yellow']);
    }
}
