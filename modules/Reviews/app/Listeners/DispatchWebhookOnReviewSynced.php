<?php

namespace Modules\Reviews\Listeners;

use Modules\Reviews\Events\ReviewSynced;
use Modules\Reviews\Services\OutboundWebhookService;

class DispatchWebhookOnReviewSynced
{
    public function __construct(private readonly OutboundWebhookService $webhookService) {}

    public function handle(ReviewSynced $event): void
    {
        $this->webhookService->dispatch('review.created', [
            'event' => 'review.created',
            'review_id' => $event->review->id,
            'location_id' => $event->review->location_id,
            'reviewer_name' => $event->review->reviewer_name,
            'star_rating' => $event->review->star_rating->value(),
            'comment' => $event->review->comment,
            'review_time' => $event->review->review_time?->toIso8601String(),
        ]);
    }
}
