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
        return (new MailMessage)
            ->subject('Confirm your email')
            ->line('Please click the button below to confirm your email address.')
            ->action('Confirm Email', url('/confirm-email?token='.$this->token))
            ->line('If you did not create an account, no further action is required.');
    }
}
