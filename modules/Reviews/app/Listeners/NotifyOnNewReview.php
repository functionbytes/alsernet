<?php

namespace Modules\Reviews\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Events\NewReviewReceivedEvent;
use Modules\Reviews\Events\ReviewSynced;
use Modules\Reviews\Notifications\NewNegativeReviewNotification;

class NotifyOnNewReview implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'notifications';

    public function handle(ReviewSynced $event): void
    {
        $review = $event->review;
        $rating = $review->star_rating->value();

        // Broadcast to the public reviews channel for real-time stats
        NewReviewReceivedEvent::dispatch($review);

        // Only notify staff for negative reviews (1-3 stars)
        if ($rating > 3) {
            return;
        }

        // Notify the location owner/connection user
        $owner = $review->location->connection->user;

        try {
            $owner->notify(new NewNegativeReviewNotification($review));

            Log::info('Negative review notification sent', [
                'review_id' => $review->id,
                'user_id' => $owner->id,
                'rating' => $rating,
                'location' => $review->location->name,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send negative review notification', [
                'review_id' => $review->id,
                'user_id' => $owner->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
