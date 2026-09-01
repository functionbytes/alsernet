<?php

namespace Modules\HelpdeskCompliance\Listeners;

use Modules\Helpdesk\Events\CustomerGdprDeleted;
use Modules\HelpdeskCompliance\Jobs\ProcessComplianceCascadeJob;
use Modules\HelpdeskCompliance\Models\ComplianceRequest;

/**
 * Encola la cascada hacia los modulos que el core no cubre (Tickets/ChatFlow/
 * EmailLog) en vez de ejecutarla de forma sincrona dentro de la request HTTP
 * del admin que dispara el borrado. Antes, un cliente con muchos tickets/
 * conversaciones podia agotar el timeout de PHP-FPM a medio camino: el
 * borrado core (ya irreversible) quedaba aplicado pero la cascada nunca
 * terminaba, sin ComplianceRequest ni reintento.
 *
 * IMPORTANTE: esto es una obligacion legal (GDPR), no una feature de UI. El
 * toggle "compliance.integration_enabled" (Settings > Integraciones) SOLO
 * gatea la navegacion/panel de HelpdeskCompliance (ver registerNav() en el
 * ServiceProvider) — nunca debe impedir que la cascada de borrado se encole.
 * Antes este listener retornaba sin encolar nada si el toggle estaba
 * apagado: el borrado core (irreversible) se aplicaba igualmente pero
 * tickets/chatflow/email logs quedaban con PII intacta y sin ningun rastro
 * en el audit trail. No reintroducir ese gate aqui.
 *
 * Solo se captura aqui lo que hace falta como datos planos (customer_id, no
 * el modelo) — en modo hard el Customer ya fue borrado por completo antes de
 * que este listener corra, y el job encolado no puede rehidratarlo.
 */
class RunComplianceCascade
{
    public function handle(CustomerGdprDeleted $event): void
    {
        // El ComplianceRequest se crea SINCRONO, en la misma request que el
        // borrado core, con status 'pending' — asi queda rastro legal desde
        // el primer instante aunque el worker de la cola tarde en recogerlo o
        // el job falle antes de poder actualizarlo. ProcessComplianceCascadeJob
        // lo transiciona a 'completed'/'failed' al terminar (ver handle()/failed()).
        $request = ComplianceRequest::create([
            'customer_id' => $event->customer->id,
            'type' => $event->hard ? ComplianceRequest::TYPE_DELETE_HARD : ComplianceRequest::TYPE_DELETE_SOFT,
            'status' => 'pending',
            'requested_by' => auth()->id(),
            'result_summary' => ['core' => $event->result],
        ]);

        ProcessComplianceCascadeJob::dispatch(
            $request->id,
            $event->customer->id,
            $event->hard,
            $event->conversationIds,
            $event->result,
            auth()->id(),
            $event->customerEmail,
            $event->customerPhones,
        );
    }
}
