<?php

namespace Modules\Engagement\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Engagement\Models\VisitorScore;

class ScoreThresholdCrossed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly VisitorScore $score,
        public readonly string $previousSegment,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('widget-session.'.$this->score->session_token),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ScoreThresholdCrossed';
    }

    public function broadcastWith(): array
    {
        return [
            'sessionToken' => $this->score->session_token,
            'previousSegment' => $this->previousSegment,
            'currentSegment' => $this->score->segment,
            'score' => $this->score->score,
            'occurredAt' => now()->toIso8601String(),
        ];
    }
}
