<?php

namespace Modules\Reviews\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Reviews\Services\NotificationService;

class DailyDigestNotification extends Notification
{
    public function __construct(
        private readonly array $stats
    ) {}

    public function via(object $notifiable): array
    {
        $service = app(NotificationService::class);
        $channels = $service->getEnabledChannels($notifiable, NotificationService::TYPE_DAILY_DIGEST);

        return array_filter($channels, fn ($channel) => $channel === 'mail');
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Daily reviews digest')
            ->greeting("Hello {$notifiable->name},")
            ->line('Here is your daily reviews summary:')
            ->line("**New Reviews:** {$this->stats['new_reviews_count']}")
            ->line("**Average Rating:** {$this->stats['average_rating']}")
            ->line("**Pending Replies:** {$this->stats['pending_replies_count']}");

        if ($this->stats['negative_reviews_count'] > 0) {
            $message->line("**Negative Reviews:** {$this->stats['negative_reviews_count']} (⚠️ requires attention)");
        }

        if ($this->stats['top_location']) {
            $message->line("**Top Location:** {$this->stats['top_location']['name']} ({$this->stats['top_location']['count']} reviews)");
        }

        return $message
            ->action('View Dashboard', route('reviews.dashboard'))
            ->line('Thank you for using our service.');
    }

    public function toArray(object $notifiable): array
    {
        return $this->stats;
    }
}
