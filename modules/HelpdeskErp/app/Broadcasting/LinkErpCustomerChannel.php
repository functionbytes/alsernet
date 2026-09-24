<?php

namespace Modules\HelpdeskErp\Broadcasting;

use App\Models\User;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Policies\CustomerPolicy;
use Modules\HelpdeskErp\Http\Controllers\Concerns\ScopesErpCustomerAccess;

/**
 * Autoriza el canal privado helpdesk.erp.customer.{customerId}, donde
 * ErpOrdersReady avisa de que los pedidos de Gestión de ese contacto ya están.
 *
 * Mismo criterio que las rutas de Gestión del chat (ErpChatController::resolve):
 * permiso helpdeskerp.view y el contacto en alguna bandeja del agente
 * (CustomerPolicy::sharesInboxWith, que deja pasar a helpdesk.manage) o
 * algún ticket suyo que el agente pueda ver (pestaña de la vista de ticket).
 */
class LinkErpCustomerChannel
{
    use ScopesErpCustomerAccess;

    public function join(mixed $user, mixed $customerId): bool
    {
        if (! $user instanceof User || ! $user->can('helpdeskerp.view')) {
            return false;
        }

        if (! is_numeric($customerId) || (int) $customerId <= 0) {
            return false;
        }

        $customer = Customer::find((int) $customerId);

        if ($customer === null) {
            return false;
        }

        return app(CustomerPolicy::class)->sharesInboxWith($user, $customer)
            || $this->canReachCustomerViaTicket($customer);
    }
}
