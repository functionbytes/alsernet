<?php

namespace Modules\Helpdesk\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationItemCreated;
use Modules\Helpdesk\Models\ConversationItem;

class SendScheduledMessageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public int $backoff = 10;

    public function __construct(
        private readonly ConversationItem $item
    ) {
        $this->onQueue('helpdesk');
    }

    public function handle(): void
    {
        $item = $this->item->fresh();

        if (! $item || ! $item->scheduled_at) {
            return;
        }

        if ($item->scheduled_at->greaterThan(now())) {
            return;
        }

        $item->update([
            'scheduled_at' => null,
            'scheduled_by' => null,
        ]);

        ConversationItemCreated::dispatch($item);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendScheduledMessageJob failed', [
            'item_id' => $this->item->id,
            'conversation_id' => $this->item->conversation_id,
            'error' => $e->getMessage(),
        ]);
    }
}
