<?php

namespace Modules\Helpdesk\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Services\Workflow\WorkflowEngine;
use Modules\HelpdeskErp\Events\CustomerErpResolved;
use Modules\HelpdeskErp\Services\ErpFactsService;

/**
 * Disparador 'conversation_erp_resolved' para el motor de workflows del inbox.
 *
 * El gemelo de RunAutomationsOnErpResolved en el lado de las conversaciones.
 * TriggerWorkflowsOnConversationCreated pasa un contexto de tres claves
 * (conversation_id, customer_id, channel) y corre cuando la búsqueda en el ERP
 * ni siquiera ha salido de la cola; aquí el contexto lleva además las señales
 * del cliente en gestión, que es lo que permite escribir condiciones sobre
 * ellas en el editor de workflows.
 */
class TriggerWorkflowsOnErpResolved implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'helpdesk';

    public int $tries = 3;

    public int $backoff = 10;

    public function handle(CustomerErpResolved $event): void
    {
        if ($event->sourceType !== 'conversation' || $event->sourceId === null) {
            return;
        }

        $conversation = Conversation::with('customer')->find($event->sourceId);

        if ($conversation === null) {
            return;
        }

        $context = [
            'conversation_id' => $conversation->id,
            'customer_id' => $conversation->customer_id,
            'channel' => $conversation->channel,
            'erp_status' => $event->status,
        ];

        if (class_exists(ErpFactsService::class)) {
            $context += app(ErpFactsService::class)->forCustomer($conversation->customer);
        }

        app(WorkflowEngine::class)->executeForTrigger('conversation_erp_resolved', $context);
    }

    public function failed(CustomerErpResolved $event, \Throwable $exception): void
    {
        Log::error('TriggerWorkflowsOnErpResolved failed', [
            'conversation_id' => $event->sourceId,
            'customer_id' => $event->customerId,
            'error' => $exception->getMessage(),
        ]);
    }
}
