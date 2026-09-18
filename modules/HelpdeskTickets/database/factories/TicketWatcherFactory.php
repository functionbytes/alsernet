<?php

namespace Modules\HelpdeskTickets\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskTickets\Models\TicketWatcher;

class TicketWatcherFactory extends Factory
{
    protected $model = TicketWatcher::class;

    public function definition(): array
    {
        return [
            'ticket_id' => null, // set via relationship
            'user_id' => 1,    // assumes at least one user; override as needed
        ];
    }
}
