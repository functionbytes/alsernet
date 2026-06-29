<?php

namespace Modules\Reviews\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Reviews\Events\ReviewSynced;

class LogReviewSync
{
    public function handle(ReviewSynced $event): void
    {
        Log::info('Review synced', [
            'review_id' => $event->review->id,
            'location_id' => $event->review->location_id,
            'rating' => $event->review->star_rating->value(),
        ]);
    }
}
