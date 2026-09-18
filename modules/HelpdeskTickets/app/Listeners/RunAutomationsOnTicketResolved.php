<?php

namespace Modules\HelpdeskTickets\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Events\TicketResolved;
use Modules\HelpdeskTickets\Services\AutomationEngine;

class RunAutomationsOnTicketResolved implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'default';

    public int $tries = 3;

    public int $backoff = 5;

    public function __construct(private readonly AutomationEngine $engine) {}

    public function handle(TicketResolved $event): void
    {
        $this->engine->handle('ticket.resolved', $event->ticket);
    }

    public function failed(TicketResolved $event, \Throwable $exception): void
    {
        Log::error('Automation listener failed on ticket.resolved', [
            'ticket_id' => $event->ticket->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
