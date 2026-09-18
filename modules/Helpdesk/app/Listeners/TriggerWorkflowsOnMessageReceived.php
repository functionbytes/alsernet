<?php

namespace Modules\Helpdesk\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\MessageReceived;
use Modules\Helpdesk\Services\Workflow\WorkflowEngine;

class TriggerWorkflowsOnMessageReceived implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'helpdesk';

    public int $tries = 3;

    public int $backoff = 10;

    public function handle(MessageReceived $event): void
    {
        $message = $event->message;

        // Recursion guard: only fire for genuinely inbound customer messages.
        // Agent / bot / outbound items (e.g. workflow-generated replies) must not
        // re-trigger workflows, otherwise actions like send_text loop indefinitely.
        if (! $message->isFromCustomer()) {
            return;
        }

        $conversation = $event->conversation;

        app(WorkflowEngine::class)->executeForTrigger('message_received', [
            'conversation_id' => $conversation->id,
            'customer_id' => $conversation->customer_id,
        ]);
    }

    public function failed(MessageReceived $event, \Throwable $exception): void
    {
        Log::error('TriggerWorkflowsOnMessageReceived failed', [
            'conversation_id' => $event->conversation->id ?? null,
            'message_id' => $event->message->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
