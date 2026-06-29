<?php

namespace Modules\HelpdeskTickets\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\HelpdeskTickets\Models\Ticket;

/**
 * Event fired when a ticket is approaching SLA breach
 */
class SlaWarning
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance
     */
    public function __construct(
        public readonly Ticket $ticket,
        public readonly ?float $percentUsed = null,
    ) {}
}
