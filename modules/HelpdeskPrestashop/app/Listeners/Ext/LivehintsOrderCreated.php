<?php

namespace Modules\HelpdeskPrestashop\Listeners\Ext;

use Modules\HelpdeskPrestashop\Events\PsOrderCreated;
use Modules\HelpdeskPrestashop\Services\Ext\LivehintsNotifier;

/**
 * order.created → "Nuevo pedido #REF de 89,90 € · Abrir". El puente manda
 * order_id, customer_id, reference y total (con IVA); sin email, así que el
 * cliente se resuelve por el vínculo de PrestaShop.
 */
class LivehintsOrderCreated
{
    public function __construct(
        private readonly LivehintsNotifier $hints
    ) {}

    public function handle(PsOrderCreated $event): void
    {
        $this->hints->guard('order_created', function () use ($event): void {
            $orderId = $event->orderId();
            if (! $orderId) {
                return;
            }

            $customer = $this->hints->resolveCustomer($event->email(), $event->customerId());
            $conversationId = $customer ? $this->hints->openConversationId($customer) : null;
            if ($conversationId === null) {
                return;
            }

            $reference = trim((string) ($event->payload['reference'] ?? ''));

            $this->hints->send($conversationId, 'order_created', (string) $orderId, [
                'order_id' => $orderId,
                'reference' => $reference !== '' ? $reference : $this->hints->cachedOrderReference($customer, $orderId),
                'total' => $event->total(),
            ]);
        });
    }
}
