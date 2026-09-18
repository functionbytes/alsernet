<?php

namespace Modules\Helpdesk\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationCreated;

class LogConversationCreated implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'helpdesk-events';

    public int $tries = 3;

    public array $backoff = [5, 15, 30];

    public function handle(ConversationCreated $event): void
    {
        $conversation = $event->conversation;

        Log::info('ConversationCreated handled', [
            'conversation_id' => $conversation->id,
            'subject' => $conversation->subject,
            'customer_id' => $conversation->customer_id ?? null,
            'assignee_id' => $conversation->assignee_id ?? null,
            'priority' => $conversation->priority,
        ]);
    }

    public function failed(ConversationCreated $event, \Throwable $exception): void
    {
        Log::error('LogConversationCreated failed', [
            'conversation_id' => $event->conversation->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
