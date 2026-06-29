<?php

namespace Modules\Page\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Page\Models\Page;
use Modules\Page\Models\PageLock;

class PageLockAcquired implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Page $page,
        public PageLock $lock
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('page.'.$this->page->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'lock-acquired';
    }

    public function broadcastWith(): array
    {
        return [
            'page_id' => $this->page->id,
            'locked_by' => [
                'id' => $this->lock->user->id,
                'name' => $this->lock->user->full_name ?? $this->lock->user->name,
                'email' => $this->lock->user->email,
            ],
            'locked_at' => $this->lock->locked_at->toIso8601String(),
            'expires_at' => $this->lock->expires_at->toIso8601String(),
        ];
    }
}
