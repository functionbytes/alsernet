<?php

namespace Modules\HelpdeskTickets\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskErp\Events\CustomerErpResolved;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Services\AutomationEngine;

/**
 * Ejecuta las reglas de "El ERP ha respondido" sobre el ticket que provocó la
 * búsqueda.
 *
 * Este disparador existe porque las reglas de ticket.created corren mientras
 * LinkCustomerToErpJob sigue en la cola helpdesk-erp: en ese momento cualquier
 * condición erp_* sería falsa para todos los tickets. Aquí el dato ya está.
 *
 * Se actúa solo sobre el ticket de origen, nunca sobre todo lo que el cliente
 * tenga abierto: resolver la ficha de un cliente no es motivo para reasignar
 * conversaciones antiguas que un agente ya estaba llevando.
 */
class RunAutomationsOnErpResolved implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'default';

    public int $tries = 3;

    public int $backoff = 5;

    public function __construct(private readonly AutomationEngine $engine) {}

    public function handle(CustomerErpResolved $event): void
    {
        if ($event->sourceType !== 'ticket' || $event->sourceId === null) {
            return;
        }

        $ticket = Ticket::with('customer')->find($event->sourceId);

        if ($ticket === null) {
            return;
        }

        $this->engine->handle('ticket.erp_resolved', $ticket);
    }

    public function failed(CustomerErpResolved $event, \Throwable $exception): void
    {
        Log::error('Automation listener failed on ticket.erp_resolved', [
            'ticket_id' => $event->sourceId,
            'customer_id' => $event->customerId,
            'error' => $exception->getMessage(),
        ]);
    }
}
