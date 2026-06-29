<?php

namespace Modules\Reviews\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Reviews\Models\ReviewReply;
use Modules\Reviews\Services\NotificationService;

class ReplyPublishedNotification extends Notification
{
    public function __construct(
        private readonly ReviewReply $reply
    ) {}

    public function via(object $notifiable): array
    {
        $service = app(NotificationService::class);
        $channels = $service->getEnabledChannels($notifiable, NotificationService::TYPE_REPLY_PUBLISHED);

        return array_filter($channels, fn ($channel) => in_array($channel, ['database', 'mail']));
    }

    public function toMail(object $notifiable): MailMessage
    {
        $review = $this->reply->review;

        return (new MailMessage)
            ->subject('Reply published to review')
            ->greeting("Hello {$notifiable->name},")
            ->line('Your reply has been successfully published to Google:')
            ->line("Location: {$review->location->name}")
            ->line("Reviewer: {$review->reviewer_name}")
            ->line("Rating: {$review->star_rating->value()} star(s)")
            ->action('View Review', route('reviews.show', $review->id))
            ->line('Thank you for using our service.');
    }

    public function toArray(object $notifiable): array
    {
        $review = $this->reply->review;

        return [
            'reply_id' => $this->reply->id,
            'review_id' => $review->id,
            'location_id' => $review->location_id,
            'location' => $review->location->name,
            'reviewer_name' => $review->reviewer_name,
            'rating' => $review->star_rating->toNumeric(),
            'published_at' => $this->reply->published_at?->toDateTimeString(),
        ];
    }
}
