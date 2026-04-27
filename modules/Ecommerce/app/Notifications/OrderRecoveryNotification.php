<?php

namespace Modules\Ecommerce\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Ecommerce\Models\Order;

class OrderRecoveryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Order $order
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $recoveryUrl = url('/orden/'.$this->order->token.'/recover');
        $name = $this->order->customer?->name
            ?? $this->order->shippingAddress?->name
            ?? 'Cliente';

        return (new MailMessage)
            ->subject('Completa tu pedido en '.config('app.name'))
            ->greeting("Hola {$name},")
            ->line('Notamos que dejaste artículos en tu carrito. ¡Tu pedido te está esperando!')
            ->line('Total: $'.number_format((float) $this->order->total, 2))
            ->action('Completar mi pedido', $recoveryUrl)
            ->line('Este enlace es personal y te llevará directamente a tu carrito guardado.')
            ->line('Si ya completaste tu compra, puedes ignorar este mensaje.');
    }
}
