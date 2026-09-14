<?php

namespace Modules\HelpdeskTickets\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\HelpdeskTickets\Models\Ticket;

/**
 * Event fired when a ticket is unassigned from its agent.
 */
class TicketUnassigned
{
    use Dispatchable, SerializesModels;

    public function __construct(public Ticket $ticket) {}
}
