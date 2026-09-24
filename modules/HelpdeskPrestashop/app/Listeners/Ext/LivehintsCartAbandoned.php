<?php

namespace Modules\HelpdeskPrestashop\Listeners\Ext;

use Modules\HelpdeskPrestashop\Events\PsCartAbandoned;
use Modules\HelpdeskPrestashop\Services\Ext\LivehintsNotifier;

/**
 * cart.abandoned → "El cliente abandonó un carrito de 150 € · Ver".
 *
 * El total del webhook lo calcula el cron del puente con p.price (sin IVA
 * ni descuentos): se manda como viene y el front lo presenta como importe
 * aproximado.
 */
class LivehintsCartAbandoned
{
    public function __construct(
        private readonly LivehintsNotifier $hints
    ) {}

    public function handle(PsCartAbandoned $event): void
    {
        $this->hints->guard('cart_abandoned', function () use ($event): void {
            $customer = $this->hints->resolveCustomer($event->email(), $event->customerId());
            $conversationId = $customer ? $this->hints->openConversationId($customer) : null;
            if ($conversationId === null) {
                return;
            }

            $items = array_values(array_filter($event->items(), 'is_array'));

            $this->hints->send($conversationId, 'cart_abandoned', (string) ($event->cartId() ?? 0), [
                'cart_id' => $event->cartId(),
                'total' => $event->total(),
                'items_count' => isset($event->payload['items_count']) ? (int) $event->payload['items_count'] : count($items),
                'first_item' => isset($items[0]['name']) ? (string) $items[0]['name'] : null,
            ]);
        });
    }
}
