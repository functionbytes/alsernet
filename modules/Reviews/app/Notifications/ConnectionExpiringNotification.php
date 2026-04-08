<?php

namespace Modules\Reviews\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Reviews\Models\ReviewGoogleConnection;
use Modules\Reviews\Services\NotificationService;

class ConnectionExpiringNotification extends Notification
{
    public function __construct(
        private readonly ReviewGoogleConnection $connection,
        private readonly int $daysRemaining
    ) {}

    public function via(object $notifiable): array
    {
        $service = app(NotificationService::class);
        $channels = $service->getEnabledChannels($notifiable, NotificationService::TYPE_CONNECTION_EXPIRING);

        return array_filter($channels, fn ($channel) => in_array($channel, ['database', 'mail']));
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Google connection expiring in {$this->daysRemaining} days")
            ->greeting("Hello {$notifiable->name},")
            ->warning()
            ->line('Your Google My Business connection is expiring soon:')
            ->line("Account: {$this->connection->name}")
            ->line("Expires: {$this->connection->token_expires_at->format('Y-m-d H:i')}")
            ->line("Days remaining: {$this->daysRemaining}")
            ->action('Reconnect Now', route('settings.reviews.connections.reconnect', $this->connection->id))
            ->line('Please reconnect to continue syncing reviews.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'connection_id' => $this->connection->id,
            'account_name' => $this->connection->name,
            'account_email' => $this->connection->google_email,
            'expires_at' => $this->connection->token_expires_at->toDateTimeString(),
            'days_remaining' => $this->daysRemaining,
        ];
    }
}
