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
        // TicketUpdateService dispara TicketStatusChanged Y TicketUpdated
        // para la MISMA operación cuando cambia status_id (TicketUpdated
        // también sirve para que el listado en vivo se entere de
        // prioridad/categoría/equipo, no solo para automatizaciones — no se
        // puede dejar de dispararlo). RunAutomationsOnTicketStatusChanged ya
        // corre este mismo trigger 'ticket.updated' para ese caso; sin este
        // guard, cada cambio de estado ejecutaba las automatizaciones DOS
        // VECES — un 'close' en la automatización reenviaba la encuesta
        // CSAT al cliente por duplicado, 'notify_agent' notificaba dos
        // veces, y run_count/last_run_at quedaban duplicados (14-sep-2026,
        // auditoría de lógica de negocio).
        if (array_key_exists('status_id', $event->changes)) {
            return;
        }

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
