<?php

namespace Modules\Page\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Page\Models\Page;

class PageLockReleased implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Page $page,
        public int $userId
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('page.'.$this->page->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'lock-released';
    }

    public function broadcastWith(): array
    {
        return [
            'page_id' => $this->page->id,
            'released_by_user_id' => $this->userId,
            'released_at' => now()->toIso8601String(),
        ];
    }
}
