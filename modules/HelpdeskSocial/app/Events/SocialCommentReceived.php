<?php

namespace Modules\HelpdeskSocial\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\HelpdeskSocial\Models\SocialComment;

class SocialCommentReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly SocialComment $comment,
    ) {
        $this->comment->load(['socialAccount', 'intent']);
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
        return 'social.comment.received';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'comment' => [
                'id' => $this->comment->id,
                'platform' => $this->comment->platform,
                'author_name' => $this->comment->author_name,
                'author_username' => $this->comment->author_username,
                'body' => $this->comment->body,
                'intent' => $this->comment->intent,
                'urgency' => $this->comment->urgency,
                'status' => $this->comment->status,
                'is_mention' => $this->comment->is_mention,
                'posted_at' => $this->comment->posted_at?->toIso8601String(),
                'social_account' => $this->comment->socialAccount ? [
                    'id' => $this->comment->socialAccount->id,
                    'name' => $this->comment->socialAccount->name,
                    'platform' => $this->comment->socialAccount->platform,
                ] : null,
            ],
        ];
    }
}
