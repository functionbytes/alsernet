<?php

namespace Modules\HelpdeskPrestashop\Listeners\Ext;

use Modules\HelpdeskPrestashop\Events\PsOrderReturned;
use Modules\HelpdeskPrestashop\Services\Ext\LivehintsNotifier;

/**
 * order.return_requested → "Nueva solicitud de devolución del pedido #REF ·
 * Ver". El puente manda return_id, order_id y customer_id.
 */
class LivehintsOrderReturned
{
    public function __construct(
        private readonly LivehintsNotifier $hints
    ) {}

    public function handle(PsOrderReturned $event): void
    {
        $this->hints->guard('order_returned', function () use ($event): void {
            $orderId = $event->orderId();
            if (! $orderId) {
                return;
            }

            $customer = $this->hints->resolveCustomer($event->email(), $event->customerId());
            $conversationId = $customer ? $this->hints->openConversationId($customer) : null;
            if ($conversationId === null) {
                return;
            }

            $returnId = isset($event->payload['return_id']) ? (int) $event->payload['return_id'] : null;

            $this->hints->send($conversationId, 'order_returned', $orderId.'|'.($returnId ?? 0), [
                'order_id' => $orderId,
                'return_id' => $returnId,
                'reference' => $this->hints->cachedOrderReference($customer, $orderId),
            ]);
        });
    }
}
