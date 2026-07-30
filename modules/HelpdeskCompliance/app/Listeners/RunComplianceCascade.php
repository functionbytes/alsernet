<?php

namespace Modules\HelpdeskCompliance\Listeners;

use Modules\Helpdesk\Events\CustomerGdprDeleted;
use Modules\HelpdeskCompliance\Jobs\ProcessComplianceCascadeJob;

/**
 * Encola la cascada hacia los modulos que el core no cubre (Tickets/ChatFlow)
 * en vez de ejecutarla de forma sincrona dentro de la request HTTP del admin
 * que dispara el borrado. Antes, un cliente con muchos tickets/conversaciones
 * podia agotar el timeout de PHP-FPM a medio camino: el borrado core (ya
 * irreversible) quedaba aplicado pero la cascada nunca terminaba, sin
 * ComplianceRequest ni reintento.
 *
 * Solo se captura aqui lo que hace falta como datos planos (customer_id, no
 * el modelo) — en modo hard el Customer ya fue borrado por completo antes de
 * que este listener corra, y el job encolado no puede rehidratarlo.
 */
class RunComplianceCascade
{
    public function handle(CustomerGdprDeleted $event): void
    {
        // The core GDPR deletion (Modules\Helpdesk\Services\Compliance\GdprDeletionService)
        // has already run by the time this listener fires — that base erasure is
        // never skipped. Only the additional Helpdesk-module cascade below
        // (Tickets/ChatFlow anonymization + ComplianceRequest tracking) is gated
        // behind the admin toggle in Settings → Integraciones.
        if (! helpdesk_compliance_enabled()) {
            return;
        }

        ProcessComplianceCascadeJob::dispatch(
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
