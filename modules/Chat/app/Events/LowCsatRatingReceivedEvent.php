<?php

namespace Modules\Chat\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Chat\Models\Csat\CsatSurveyResponse;

class LowCsatRatingReceivedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public CsatSurveyResponse $survey
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.'.$this->survey->conversation_id),
            new PrivateChannel('account.'.$this->survey->conversation->account_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'csat.low_rating';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'survey' => [
                'id' => $this->survey->id,
                'conversation_id' => $this->survey->conversation_id,
                'rating' => $this->survey->rating,
                'rating_emoji' => $this->survey->rating_emoji,
                'feedback' => $this->survey->feedback,
                'submitted_at' => $this->survey->submitted_at->toIso8601String(),
            ],
            'conversation' => [
                'id' => $this->survey->conversation->id,
                'contact_name' => $this->survey->conversation->customer->name ?? 'Unknown',
                'assignee_name' => $this->survey->conversation->assignee->name ?? null,
            ],
        ];
    }
}
