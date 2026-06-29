<?php

namespace Modules\Ecommerce\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConfirmEmailNotification extends Notification
{
    use Queueable;

    public function __construct(public string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Web bridge URL detects User-Agent and redirects to deep link on mobile.
        $url = url('/email-bridge/verify-email').'?token='.$this->token;

        return (new MailMessage)
            ->subject('Confirma tu correo electrónico')
            ->greeting('Hola '.($notifiable->name ?? '').',')
            ->line('Confirma tu cuenta tocando el siguiente botón.')
            ->action('Confirmar email', $url)
            ->line('Si no creaste una cuenta, ignora este mensaje.');
    }
}
