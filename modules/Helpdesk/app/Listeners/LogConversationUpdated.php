<?php

namespace Modules\Helpdesk\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationUpdated;

class LogConversationUpdated implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'helpdesk-events';

    public int $tries = 3;

    public array $backoff = [5, 15, 30];

    public function handle(ConversationUpdated $event): void
    {
        $conversation = $event->conversation;

        Log::info('ConversationUpdated handled', [
            'conversation_id' => $conversation->id,
            'subject' => $conversation->subject,
            'priority' => $conversation->priority,
            'updated_at' => $conversation->updated_at?->toIso8601String(),
        ]);
    }

    public function failed(ConversationUpdated $event, \Throwable $exception): void
    {
        Log::error('LogConversationUpdated failed', [
            'conversation_id' => $event->conversation->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
