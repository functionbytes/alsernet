<?php

namespace Modules\Mailrelay\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent to administrators when a subscriber sync fails permanently.
 */
class SyncFailedNotification extends Notification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected readonly array $subscriberInfo,
        protected readonly string $errorMessage,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Mailrelay: Subscriber sync failed')
            ->greeting('Subscriber sync failure')
            ->line('A subscriber sync has failed permanently after all retry attempts.')
            ->line('**Subscriber:** '.($this->subscriberInfo['email'] ?? 'Unknown'))
            ->line('**Subscriber ID:** '.($this->subscriberInfo['id'] ?? 'N/A'))
            ->line('**Error:** '.$this->errorMessage)
            ->line('Please review the application logs for more details.')
            ->action('View Subscribers', url('/mailrelay/subscribers'));
    }
}
