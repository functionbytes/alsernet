<?php

namespace Modules\Helpdesk\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Helpdesk\Models\Customer;

/**
 * Fired by GdprDeletionService after a customer GDPR deletion (soft or hard).
 *
 * This is the extension point that lets other modules (HelpdeskCompliance)
 * cascade the erasure to records the core does not know about — tickets,
 * chatbot sessions, email logs, translation cache — without the core taking a
 * hard dependency on them. Listeners run synchronously so the cascade completes
 * within the same request (the customer may be hard-deleted, so re-fetching in a
 * queued job would fail).
 *
 * @property array<int, int> $conversationIds Ids captured BEFORE deletion (hard
 *                                            delete force-removes conversations, so they must be passed along).
 */
class CustomerGdprDeleted
{
    use Dispatchable, SerializesModels;

    /**
     * $customerEmail / $customerPhones se capturan ANTES del borrado, igual que
     * $conversationIds: cuando este evento se despacha, el soft delete ya
     * anonimizó el Customer (email/teléfonos a null) y el hard delete lo
     * eliminó por completo, así que los listeners que emparejan registros por
     * email/teléfono (p. ej. los expedientes de HelpdeskDocument, vinculados
     * por customer_email / customer_cellphone_normalized) no pueden leerlos
     * del modelo. Son opcionales para mantener compatibilidad con cualquier
     * despachador existente del evento.
     *
     * @param  array<int, int>  $conversationIds
     * @param  array{deleted: int, anonymized: int}  $result
     * @param  array<int, string>  $customerPhones  Teléfonos del cliente (phone y whatsapp_phone) sin normalizar.
     */
    public function __construct(
        public readonly Customer $customer,
        public readonly bool $hard,
        public readonly array $conversationIds,
        public readonly array $result,
        public readonly ?string $customerEmail = null,
        public readonly array $customerPhones = [],
    ) {}
}
