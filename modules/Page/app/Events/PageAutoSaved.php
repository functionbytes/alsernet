<?php

namespace Modules\Page\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Page\Models\Page;
use Modules\Page\Models\PageAutoSave;

class PageAutoSaved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Page $page,
        public PageAutoSave $autoSave
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('page.'.$this->page->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'auto-saved';
    }

    public function broadcastWith(): array
    {
        return [
            'page_id' => $this->page->id,
            'saved_by' => [
                'id' => $this->autoSave->user->id,
                'name' => $this->autoSave->user->full_name ?? $this->autoSave->user->name,
            ],
            'saved_at' => $this->autoSave->saved_at->toIso8601String(),
            'fields_changed' => array_keys($this->autoSave->data ?? []),
        ];
    }
}
