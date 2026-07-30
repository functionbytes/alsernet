<?php

namespace Modules\HelpdeskTickets\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Services\AutomationEngine;

class RunAutomationsOnTicketCreated implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'default';

    public int $tries = 3;

    public int $backoff = 5;

    public function __construct(private readonly AutomationEngine $engine) {}

    public function handle(TicketCreated $event): void
    {
        $this->engine->handle('ticket.created', $event->ticket);
    }

    public function failed(TicketCreated $event, \Throwable $exception): void
    {
        Log::error('Automation listener failed on ticket.created', [
            'ticket_id' => $event->ticket->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
