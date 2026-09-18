<?php

namespace Modules\Helpdesk\Support\Concerns;

use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Policies\CustomerPolicy;

/**
 * Para controllers de modulos satelite (HelpdeskErp, HelpdeskPrestashop...)
 * que exponen datos de un Customer bajo su PROPIO permiso de modulo
 * (helpdeskerp.view, helpdeskprestashop.view...). Ademas de ese permiso,
 * aplica el MISMO aislamiento por inbox que ya protege al Customer en el
 * core (CustomerPolicy::sharesInboxWith) — sin esto, cualquier agente con
 * el permiso del modulo satelite podia consultar los datos (balance,
 * pedidos, carritos) de un cliente de OTRO inbox.
 *
 * Si el Customer SI existe, se aplica sharesInboxWith() igual que en el core —
 * un agente sin inbox compartido no lo ve.
 *
 * Si NO existe ningun Customer local con ese email/external_id (prospecto):
 * por defecto se permite (no hay registro que aislar). Pero un consumidor que
 * expone DATOS REALES de no-clientes (ERP: balance/credito/pedidos) puede pasar
 * $prospectPermission para exigir un permiso dedicado a ese caso — asi la
 * consulta de un no-cliente arbitrario queda gateada a un rol de confianza en
 * lugar de abierta a cualquier titular del permiso base del modulo.
 */
trait ScopesCustomerByInbox
{
    private function assertScopedToCustomerEmail(string $email, ?string $prospectPermission = null): void
    {
        $this->assertScopedToResolvedCustomer(
            Customer::where('email', $email)->first(),
            $prospectPermission
        );
    }

    private function assertScopedToExternalId(string $platform, int|string $externalId, ?string $prospectPermission = null): void
    {
        $customer = Customer::whereHas(
            'externalIds',
            fn ($q) => $q->where('platform', $platform)->where('external_id', (string) $externalId)
        )->first();

        $this->assertScopedToResolvedCustomer($customer, $prospectPermission);
    }

    private function assertScopedToResolvedCustomer(?Customer $customer, ?string $prospectPermission): void
    {
        if ($customer) {
            if (! app(CustomerPolicy::class)->sharesInboxWith(auth()->user(), $customer)) {
                abort(403, 'Sin autorización sobre este cliente.');
            }

            return;
        }

        if ($prospectPermission !== null && ! auth()->user()?->can($prospectPermission)) {
            abort(403, 'Sin autorización para consultar datos de no-clientes.');
        }
    }
}
