<?php

namespace Modules\HelpdeskTickets\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\HelpdeskTickets\Models\Ticket;

/**
 * Event fired when a ticket is reopened
 */
class TicketReopened
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance
     */
    public function __construct(public Ticket $ticket) {}
}
