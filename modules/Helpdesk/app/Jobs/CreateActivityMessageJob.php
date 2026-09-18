<?php

namespace Modules\Helpdesk\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;

class CreateActivityMessageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public int $backoff = 10;

    public function __construct(
        public readonly int $conversationId,
        public readonly string $activityType,
        public readonly string $body,
        public readonly array $data = [],
        public readonly ?int $authorId = null,
    ) {
        $this->onQueue('helpdesk-events');
    }

    public function handle(): void
    {
        $conversation = Conversation::find($this->conversationId);

        if (! $conversation) {
            return;
        }

        ConversationItem::create([
            'conversation_id' => $conversation->id,
            'item_type' => 'activity',
            'type' => 'activity',
            'activity_type' => $this->activityType,
            'activity_data' => $this->data,
            'body' => $this->body,
            'user_id' => $this->authorId,
            'is_internal' => false,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('CreateActivityMessageJob failed', [
            'conversation_id' => $this->conversationId,
            'activity_type' => $this->activityType,
            'error' => $e->getMessage(),
        ]);
    }
}
