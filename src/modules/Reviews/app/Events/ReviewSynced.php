<?php

namespace Modules\Reviews\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Reviews\Models\Review;

class ReviewSynced
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Review $review
    ) {}
}
