<?php

namespace Modules\HelpdeskErp\Http\Controllers\Concerns;

use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Policies\CustomerPolicy;

/**
 * Alcance de cliente de las rutas de Gestión en el chat.
 *
 * Igual que ScopesCustomerByInbox (el cliente tiene que estar en alguna
 * bandeja del agente), con una vía más: la pestaña «Tienda y Gestión» de la
 * vista de ticket. Si el agente puede ver (TicketPolicy::view) algún ticket
 * de ese cliente, también puede ver su Gestión, aunque el cliente no tenga
 * conversaciones en sus bandejas.
 */
trait ScopesErpCustomerAccess
{
    /** Tickets del cliente que se revisan como mucho (los más recientes). */
    private int $erpTicketScopeLimit = 50;

    private function assertErpCustomerAccess(Customer $customer): void
    {
        // Primero la bandeja (lo habitual y sin consultas extra); los tickets
        // solo si eso falla.
        $user = auth()->user();

        if ($user !== null && (app(CustomerPolicy::class)->sharesInboxWith($user, $customer)
            || $this->canReachCustomerViaTicket($customer))) {
            return;
        }

        abort(403, 'Sin autorización sobre este cliente.');
    }

    private function canReachCustomerViaTicket(Customer $customer): bool
    {
        $user = auth()->user();
        $ticketClass = 'Modules\\HelpdeskTickets\\Models\\Ticket';

        if ($user === null || ! class_exists($ticketClass)) {
            return false;
        }

        try {
            return $ticketClass::query()
                ->where('customer_id', $customer->id)
                ->latest('id')
                ->limit($this->erpTicketScopeLimit)
                ->get()
                ->contains(fn ($ticket): bool => $user->can('view', $ticket));
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }
}
