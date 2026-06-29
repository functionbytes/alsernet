<?php

namespace Modules\Ecommerce\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConfirmDeletionRequestNotification extends Notification
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
            ->subject('Confirm Account Deletion')
            ->line('You have requested to delete your account. Please confirm this action.')
            ->action('Confirm Deletion', url('/confirm-deletion?token='.$this->token))
            ->line('This action is irreversible.');
    }
}
