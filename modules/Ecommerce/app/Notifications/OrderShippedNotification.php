<?php

namespace Modules\Ecommerce\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Ecommerce\Models\Order;

class OrderShippedNotification extends Notification implements ShouldQueue
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
        $shipment = $this->order->shipments->first();
        $trackingLine = $shipment?->tracking_id
            ? "Número de seguimiento: {$shipment->tracking_id}"
            : 'Pronto recibirás más información sobre el estado de tu envío.';

        return (new MailMessage)
            ->subject("Tu orden #{$this->order->code} fue enviada")
            ->greeting("Hola {$notifiable->name},")
            ->line("Tu orden #{$this->order->code} ha sido despachada.")
            ->line($trackingLine)
            ->action('Ver orden', route('checkout.confirmation', $this->order->code))
            ->line('Gracias por tu compra.');
    }

    public function toArray(object $notifiable): array
    {
        $shipment = $this->order->shipments->first();
        $message = "Tu orden {$this->order->code} fue enviada.";

        if ($shipment?->tracking_id) {
            $message .= " Seguimiento: {$shipment->tracking_id}";
        }

        return [
            'type' => 'order_shipped',
            'title' => 'Orden enviada',
            'message' => $message,
            'entity_id' => $this->order->id,
        ];
    }
}
