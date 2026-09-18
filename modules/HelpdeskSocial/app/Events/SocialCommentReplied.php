<?php

namespace Modules\HelpdeskSocial\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\HelpdeskSocial\Models\SocialComment;

class SocialCommentReplied implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly SocialComment $comment,
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
            new PrivateChannel('helpdesk.social.comment.'.$this->comment->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'social.comment.replied';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'comment' => [
                'id' => $this->comment->id,
                'status' => $this->comment->status,
                'reply_body' => $this->comment->reply_body,
                'reply_type' => $this->comment->reply_type,
                'replied_at' => $this->comment->replied_at?->toIso8601String(),
                'replied_by_user_id' => $this->comment->replied_by_user_id,
            ],
        ];
    }
}
