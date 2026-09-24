<?php

namespace Modules\Helpdesk\Policies;

use App\Models\User;
use Modules\Helpdesk\Models\AgentInboxCapacity;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.customers.view');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('helpdesk.customers.view')
            && ($this->sharesInboxWith($user, $customer) || $this->reachableViaTicket($user, $customer));
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.customers.create');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('helpdesk.customers.update')
            && $this->sharesInboxWith($user, $customer);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('helpdesk.customers.delete')
            && $this->sharesInboxWith($user, $customer);
    }

    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.customers.manage');
    }

    public function restore(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('helpdesk.customers.manage');
    }

    public function forceDelete(User $user, Customer $customer): bool
    {
        return $user->hasAnyRole(['super-admin']);
    }

    /**
     * Aislamiento por inbox: un agente solo accede a un cliente con el que
     * comparte al menos una conversación en uno de sus inboxes asignados. Los
     * gestores (helpdesk.manage o helpdesk.customers.manage) acceden a todos.
     *
     * Publico a proposito: otros modulos satelite (HelpdeskErp, HelpdeskPrestashop)
     * exponen datos del mismo Customer bajo su propio permiso de modulo y
     * necesitan aplicar este MISMO aislamiento ademas de su propio permiso,
     * sin duplicar la logica de inboxes asignados.
     */
    public function sharesInboxWith(User $user, Customer $customer): bool
    {
        if ($user->hasPermissionTo('helpdesk.manage') || $user->hasPermissionTo('helpdesk.customers.manage')) {
            return true;
        }

        $inboxIds = AgentInboxCapacity::query()
            ->where('user_id', $user->id)
            ->pluck('inbox_id');

        if ($inboxIds->isEmpty()) {
            return false;
        }

        // Igual que Customer::scopeForAgent(): comparte inbox si hay una
        // conversación en un inbox asignado O si el cliente está asociado al
        // inbox vía el pivot helpdesk_customer_inboxes (contactos creados o
        // importados que aún no tienen conversación).
        return Conversation::query()
            ->where('customer_id', $customer->id)
            ->whereIn('inbox_id', $inboxIds)
            ->exists()
            || $customer->inboxes()
                ->whereIn('helpdesk_customer_inboxes.inbox_id', $inboxIds)
                ->exists();
    }

    /**
     * Solo para ver (no editar ni borrar): quien puede ver un ticket del
     * cliente puede ver al cliente, aunque no compartan bandeja (la pestaña
     * «Tienda y Gestión» de la vista de ticket). Se revisan como mucho los 50
     * tickets más recientes del cliente.
     */
    public function reachableViaTicket(User $user, Customer $customer): bool
    {
        $ticketClass = 'Modules\\HelpdeskTickets\\Models\\Ticket';

        if (! class_exists($ticketClass)) {
            return false;
        }

        try {
            return $ticketClass::query()
                ->where('customer_id', $customer->id)
                ->latest('id')
                ->limit(50)
                ->get()
                ->contains(fn ($ticket): bool => $user->can('view', $ticket));
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }
}
