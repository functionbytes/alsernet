<?php

namespace Modules\Reviews\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Modules\Reviews\Models\Review;

class NewReviewReceivedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Review $review) {}

    public function broadcastOn(): array
    {
        return [new Channel('reviews')];
    }

    public function broadcastAs(): string
    {
        return 'review.new';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->review->id,
            'rating' => $this->review->star_rating->value(),
            'reviewer' => $this->review->reviewer_name ?? 'Anonimo',
            'comment' => Str::limit($this->review->comment ?? '', 100),
            'created_at' => $this->review->created_at->toISOString(),
        ];
    }
}
