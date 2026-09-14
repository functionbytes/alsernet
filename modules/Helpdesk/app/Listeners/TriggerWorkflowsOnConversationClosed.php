<?php

namespace Modules\Helpdesk\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationClosed;
use Modules\Helpdesk\Services\Workflow\WorkflowEngine;

class TriggerWorkflowsOnConversationClosed implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'helpdesk';

    public int $tries = 3;

    public int $backoff = 10;

    public function handle(ConversationClosed $event): void
    {
        $conversation = $event->conversation;

        app(WorkflowEngine::class)->executeForTrigger('conversation_closed', [
            'conversation_id' => $conversation->id,
            'customer_id' => $conversation->customer_id,
            'channel' => $conversation->channel,
        ]);
    }

    public function failed(ConversationClosed $event, \Throwable $exception): void
    {
        Log::error('TriggerWorkflowsOnConversationClosed failed', [
            'conversation_id' => $event->conversation->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
