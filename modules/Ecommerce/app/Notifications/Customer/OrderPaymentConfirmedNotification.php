<?php

namespace Modules\Ecommerce\Notifications\Customer;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Modules\Ecommerce\Models\Order;
use Modules\Notification\Channels\FcmCustomerChannel;

class OrderPaymentConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Order $order
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', FcmCustomerChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order.payment.confirmed',
            'title' => 'Pago confirmado',
            'message' => "Hemos recibido el pago de tu pedido {$this->order->code}.",
            'entity_id' => $this->order->id,
            'action_url' => '/api/v1/ecommerce/orders/'.$this->order->id,
        ];
    }

    public function toFcm(object $notifiable): array
    {
        return [
            'title' => 'Pago confirmado',
            'body' => "Hemos recibido el pago de tu pedido {$this->order->code}.",
            'data' => [
                'type' => 'order.payment.confirmed',
                'orderId' => (string) $this->order->id,
                'orderCode' => $this->order->code,
            ],
        ];
    }
}
