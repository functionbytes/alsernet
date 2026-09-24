<?php

namespace Modules\HelpdeskPrestashop\Listeners\Ext;

use Modules\HelpdeskPrestashop\Events\PsOrderStatusChanged;
use Modules\HelpdeskPrestashop\Services\Ext\LivehintsNotifier;

/**
 * order.status_changed → "El pedido #REF ha pasado a Enviado · Abrir".
 * El puente manda ids de estado; el nombre sale del catálogo de estados
 * (cacheado 1 h) y la referencia del contexto ya cacheado del cliente.
 */
class LivehintsOrderStatus
{
    public function __construct(
        private readonly LivehintsNotifier $hints
    ) {}

    public function handle(PsOrderStatusChanged $event): void
    {
        $this->hints->guard('order_status', function () use ($event): void {
            $orderId = $event->orderId();
            $newStatus = $event->newStatus() !== null ? (int) $event->newStatus() : null;
            if (! $orderId || ! $newStatus || $newStatus === (int) $event->oldStatus()) {
                return;
            }

            $customer = $this->hints->resolveCustomer($event->payload['email'] ?? null, $event->customerId());
            $conversationId = $customer ? $this->hints->openConversationId($customer) : null;
            if ($conversationId === null) {
                return;
            }

            $this->hints->send($conversationId, 'order_status', $orderId.'|'.$newStatus, [
                'order_id' => $orderId,
                'reference' => $this->hints->cachedOrderReference($customer, $orderId),
                'state_id' => $newStatus,
                'state_name' => $this->hints->orderStateName($newStatus),
            ]);
        });
    }
}
