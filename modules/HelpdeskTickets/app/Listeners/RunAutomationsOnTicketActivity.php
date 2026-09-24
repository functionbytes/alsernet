<?php

namespace Modules\HelpdeskTickets\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Events\MessageAdded;
use Modules\HelpdeskTickets\Events\SlaBreached;
use Modules\HelpdeskTickets\Events\TicketReopened;
use Modules\HelpdeskTickets\Models\TicketItem;
use Modules\HelpdeskTickets\Services\AutomationEngine;

/**
 * Disparadores de automatización que faltaban (24-sep-2026): "el cliente
 * respondió", "ticket reabierto" y "SLA incumplido". Son los que más piden
 * las reglas reales (reasignar si el cliente insiste, avisar al jefe de
 * equipo si vence el SLA) y ninguno de los seis disparadores anteriores los
 * cubría.
 *
 * Mismo patrón que RunAutomationsOnTicketClosed: en cola 'default', tres
 * intentos. Solo se dispara con mensajes del cliente: una respuesta de
 * agente creada por la propia automatización no puede volver a lanzarla.
 */
class RunAutomationsOnTicketActivity implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'default';

    public int $tries = 3;

    public int $backoff = 5;

    public function __construct(private readonly AutomationEngine $engine) {}

    public function handle(MessageAdded|TicketReopened|SlaBreached $event): void
    {
        if ($event instanceof MessageAdded) {
            $item = $event->message;

            if (! $item instanceof TicketItem || $item->is_internal || ! $item->isFromCustomer() || ! $item->ticket) {
                return;
            }

            $this->engine->handle('ticket.customer_replied', $item->ticket);

            return;
        }

        $trigger = $event instanceof TicketReopened ? 'ticket.reopened' : 'ticket.sla_breached';

        $this->engine->handle($trigger, $event->ticket);
    }

    public function failed(MessageAdded|TicketReopened|SlaBreached $event, \Throwable $exception): void
    {
        Log::error('Automation listener failed on ticket activity', [
            'event' => get_class($event),
            'error' => $exception->getMessage(),
        ]);
    }
}
