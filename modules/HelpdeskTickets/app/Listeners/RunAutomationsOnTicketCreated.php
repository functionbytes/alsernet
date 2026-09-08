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

    /**
     * 'default' es deliberado: las automatizaciones son cortas y no compiten
     * con el chat en tiempo real. Ojo, esta cola SOLO la atiende el contenedor
     * webadmin-worker (queue:work --queue=default,sync,exports); estuvo parado
     * dos días y por eso ninguna automatización se ejecutaba (162.000 jobs
     * acumulados, 7-sep-2026). Si vuelve a pasar, mirar ese contenedor antes
     * que el código.
     */
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
