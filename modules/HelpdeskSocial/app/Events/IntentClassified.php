<?php

namespace Modules\HelpdeskSocial\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Models\SocialIntent;

class IntentClassified implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly SocialComment $comment,
        public readonly SocialIntent $intent,
    ) {
        $this->comment->load(['socialAccount']);
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('helpdesk.social.inbox'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'social.intent.classified';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'comment_id' => $this->comment->id,
            'intent' => $this->intent->intent,
            'confidence' => $this->intent->confidence,
            'urgency' => $this->intent->urgency,
            'classifier' => $this->intent->classifier,
        ];
    }
}
