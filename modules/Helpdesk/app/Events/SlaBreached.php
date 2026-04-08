<?php

namespace Modules\Helpdesk\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Helpdesk\Models\Ticket;

/**
 * Event fired when a ticket SLA is breached
 */
class SlaBreached
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance
     */
    public function __construct(public Ticket $ticket) {}
}
