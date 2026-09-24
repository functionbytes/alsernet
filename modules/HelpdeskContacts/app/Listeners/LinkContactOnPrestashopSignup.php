<?php

namespace Modules\HelpdeskContacts\Listeners;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Customer;

/**
 * PrestaShop avisa (customer.created) cuando alguien se registra en la tienda.
 * Si ese email ya es un contacto del helpdesk sin cuenta de tienda vinculada,
 * se vincula en el momento: la ficha 360 ve sus pedidos sin esperar a que el
 * agente pulse "Sincronizar". No crea contactos nuevos: registrarse en la
 * tienda no es escribir al servicio de atención.
 *
 * Recibe Modules\HelpdeskPrestashop\Events\PsCustomerCreated (sin type-hint
 * para no acoplar el módulo cuando HelpdeskPrestashop está apagado).
 */
class LinkContactOnPrestashopSignup
{
    public function handle(object $event): void
    {
        $psId = method_exists($event, 'customerId') ? $event->customerId() : null;
        $email = method_exists($event, 'email') ? trim((string) $event->email()) : '';

        if ($psId === null || $psId <= 0 || $email === '' || str_ends_with($email, '@anonymous.local')) {
            return;
        }

        $customer = Customer::query()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
            ->with('externalIds')
            ->first();

        if (! $customer || $customer->externalIdFor('prestashop') !== null) {
            return;
        }

        try {
            $customer->linkExternalId('prestashop', (string) $psId, [
                'linked_via' => 'ps_signup',
                'email' => $email,
            ]);
        } catch (QueryException $e) {
            // Unique (platform, external_id): esa cuenta ya es de otro contacto.
            if ($e->getCode() !== '23000') {
                throw $e;
            }
            Log::info('HelpdeskContacts: cuenta PS ya vinculada a otro contacto', ['customer_id' => $customer->id, 'ps_id' => $psId]);
        }
    }
}
