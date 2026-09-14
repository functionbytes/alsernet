<?php

namespace Modules\HelpdeskTickets\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Events\TicketAssigned;
use Modules\HelpdeskTickets\Services\AutomationEngine;

class RunAutomationsOnTicketAssigned implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'default';

    public int $tries = 3;

    public int $backoff = 5;

    public function __construct(private readonly AutomationEngine $engine) {}

    public function handle(TicketAssigned $event): void
    {
        $this->engine->handle('ticket.assigned', $event->ticket);
    }

    public function failed(TicketAssigned $event, \Throwable $exception): void
    {
        Log::error('Automation listener failed on ticket.assigned', [
            'ticket_id' => $event->ticket->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
