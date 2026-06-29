<?php

namespace Modules\Reviews\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewModeration;

class ReviewModerationUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Review $review,
        public readonly ReviewModeration $moderation,
        public readonly string $action
    ) {}
}
