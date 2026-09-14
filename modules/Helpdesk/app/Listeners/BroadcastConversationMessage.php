<?php

namespace Modules\Helpdesk\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationMessageCreated;

class BroadcastConversationMessage implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'helpdesk-events';

    public int $tries = 3;

    public array $backoff = [5, 15, 30];

    public function handle(ConversationMessageCreated $event): void
    {
        $item = $event->item;

        Log::info('ConversationMessageCreated handled', [
            'item_id' => $item->id,
            'conversation_id' => $item->conversation_id,
            'type' => $item->type,
            'author_id' => $item->author_id ?? null,
            'is_internal' => $item->is_internal,
        ]);
    }

    public function failed(ConversationMessageCreated $event, \Throwable $exception): void
    {
        Log::error('BroadcastConversationMessage failed', [
            'item_id' => $event->item->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
