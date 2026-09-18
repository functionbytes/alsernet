<?php

namespace Modules\HelpdeskTickets\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\HelpdeskTickets\Models\Ticket;

/**
 * Event fired when a ticket is resolved (resolved_at set, before/without closing).
 * Enables the `ticket.resolved` automation trigger and any resolution-time hooks.
 */
class TicketResolved
{
    use Dispatchable, SerializesModels;

    public function __construct(public Ticket $ticket) {}
}
