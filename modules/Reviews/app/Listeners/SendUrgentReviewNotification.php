<?php

namespace Modules\Reviews\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Events\ReviewSynced;
use Modules\Reviews\Notifications\UrgentReviewNotification;
use Modules\Reviews\Services\NotificationService;

class SendUrgentReviewNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public function handle(ReviewSynced $event): void
    {
        $review = $event->review;
        $rating = $review->star_rating->value();
        $threshold = config('reviews.general.notifications.urgent_rating_threshold', 2);

        if ($rating > $threshold) {
            return;
        }

        if ($review->hasGoogleReply()) {
            return;
        }

        $owner = $review->location->connection->user;

        $notificationService = app(NotificationService::class);

        if (! $notificationService->checkPreferences($owner, NotificationService::TYPE_URGENT_REVIEW)) {
            return;
        }

        try {
            $owner->notify(new UrgentReviewNotification($review));

            Log::info('Urgent review notification sent', [
                'review_id' => $review->id,
                'user_id' => $owner->id,
                'rating' => $rating,
                'location' => $review->location->name,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send urgent review notification', [
                'review_id' => $review->id,
                'user_id' => $owner->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
