<?php

namespace Modules\Social\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Social\Models\Mention;

class NewMentionReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Mention $mention
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel("account.{$this->mention->account_id}"),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->mention->id,
            'type' => $this->mention->type,
            'author_name' => $this->mention->author_name,
            'author_username' => $this->mention->author_username,
            'author_avatar' => $this->mention->author_avatar,
            'content' => $this->mention->content,
            'network' => $this->mention->socialAccount->network->value,
            'network_icon' => $this->mention->network_icon,
            'sentiment' => $this->mention->sentiment,
            'created_at' => $this->mention->created_at->toIso8601String(),
            'url' => route('admin.social.inbox.index'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'mention.received';
    }
}
