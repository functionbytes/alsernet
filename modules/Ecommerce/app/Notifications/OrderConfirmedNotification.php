<?php

namespace Modules\Ecommerce\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Ecommerce\Models\Order;

class OrderConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Order $order
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $actionUrl = route('checkout.confirmation', $this->order->code);

        return (new MailMessage)
            ->subject("Tu orden #{$this->order->code} fue confirmada")
            ->greeting("Hola {$notifiable->name},")
            ->line("Tu orden #{$this->order->code} ha sido recibida y está siendo procesada.")
            ->line('Total: $'.number_format((float) $this->order->total, 2))
            ->action('Ver orden', $actionUrl)
            ->line('Gracias por tu compra.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_confirmed',
            'title' => 'Orden confirmada',
            'message' => "Tu orden {$this->order->code} fue recibida.",
            'entity_id' => $this->order->id,
        ];
    }
}
