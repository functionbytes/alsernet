<?php

namespace Modules\Ecommerce\Notifications\Customer;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Modules\Ecommerce\Models\Order;
use Modules\Notification\Channels\FcmCustomerChannel;

class OrderStatusChangedNotification extends Notification implements ShouldQueue
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
            'type' => 'order.status.changed',
            'title' => 'Tu pedido ha sido actualizado',
            'message' => "El pedido {$this->order->code} ahora está {$this->order->status?->value}.",
            'entity_id' => $this->order->id,
            'action_url' => '/api/v1/ecommerce/orders/'.$this->order->id,
        ];
    }

    public function toFcm(object $notifiable): array
    {
        return [
            'title' => 'Pedido actualizado',
            'body' => "Tu pedido {$this->order->code} ahora está {$this->order->status?->value}.",
            'data' => [
                'type' => 'order.status.changed',
                'orderId' => (string) $this->order->id,
                'orderCode' => $this->order->code,
            ],
        ];
    }
}
