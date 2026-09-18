<?php

namespace Modules\HelpdeskTickets\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Events\TicketUpdated;
use Modules\HelpdeskTickets\Services\AutomationEngine;

class RunAutomationsOnTicketUpdated implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'default';

    public int $tries = 3;

    public int $backoff = 5;

    public function __construct(private readonly AutomationEngine $engine) {}

    public function handle(TicketUpdated $event): void
    {
        $this->engine->handle('ticket.updated', $event->ticket);
    }

    public function failed(TicketUpdated $event, \Throwable $exception): void
    {
        Log::error('Automation listener failed on ticket.updated', [
            'ticket_id' => $event->ticket->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
