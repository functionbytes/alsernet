<?php

namespace Modules\Helpdesk\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Helpdesk\Models\Ticket;

/**
 * Event fired when a ticket is assigned to an agent
 */
class TicketAssigned
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance
     */
    public function __construct(public Ticket $ticket, public User $agent) {}
}
