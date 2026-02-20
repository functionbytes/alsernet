<?php

namespace Modules\Reviews\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Reviews\Models\Review;

class NewNegativeReviewNotification extends Notification
{
    public function __construct(
        private readonly Review $review
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $rating = $this->review->star_rating->value();

        return (new MailMessage)
            ->subject("New {$rating}-star review for {$this->review->location->name}")
            ->greeting("Hello {$notifiable->name},")
            ->line('A new review has been posted for your location:')
            ->line("**{$this->review->location->name}** - {$rating} star(s)")
            ->line("Reviewer: {$this->review->reviewer_name}")
            ->when(
                $this->review->comment,
                fn (MailMessage $mail) => $mail->line("Comment: {$this->review->comment}")
            )
            ->action('View Review', route('reviews.show', $this->review->id))
            ->line('Thank you for using our service.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'review_id' => $this->review->id,
            'location_id' => $this->review->location_id,
            'location' => $this->review->location->name,
            'reviewer_name' => $this->review->reviewer_name,
            'rating' => $this->review->star_rating->toNumeric(),
            'comment' => $this->review->comment,
            'review_time' => $this->review->review_time->toDateTimeString(),
        ];
    }
}
