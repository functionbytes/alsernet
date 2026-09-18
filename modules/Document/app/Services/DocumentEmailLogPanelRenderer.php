<?php

namespace Modules\Document\Services;

use Illuminate\Support\Facades\View;
use Modules\Document\Entities\Document;
use Modules\HelpdeskEmailActivity\Contracts\EmailLogEntityPanelRenderer;
use Modules\HelpdeskEmailActivity\Models\EmailLog;

/**
 * Lado Document del punto de extensión EntityPanelRegistry de
 * HelpdeskEmailActivity (ver el contrato EmailLogEntityPanelRenderer y su primer
 * ejemplo real: Modules\HelpdeskTickets\Services\TicketEmailLogPanelRenderer)
 * — cuando el detalle de un email referencia un Document (entity_type ===
 * Document::class), muestra un resumen mínimo: los datos del cliente ya
 * viven directamente en columnas del propio Document (customer_firstname/
 * customer_lastname/customer_email), no hace falta ningún join.
 */
class DocumentEmailLogPanelRenderer implements EmailLogEntityPanelRenderer
{
    public function supports(string $entityType): bool
    {
        return $entityType === Document::class;
    }

    public function render(EmailLog $emailLog): ?string
    {
        $document = Document::with(['documentType', 'status'])->find($emailLog->entity_id);

        if (! $document) {
            return null;
        }

        return View::make('documents::partials.email-log-panel', [
            'document' => $document,
        ])->render();
    }
}
