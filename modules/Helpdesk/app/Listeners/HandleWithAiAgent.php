<?php

namespace Modules\Helpdesk\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Services\AI\AiAgentService;

class HandleWithAiAgent implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'helpdesk-events';

    public int $tries = 2;

    public array $backoff = [5, 15];

    public function __construct(
        private readonly AiAgentService $agentService
    ) {}

    public function handle(ConversationMessageCreated $event): void
    {
        if (! $this->agentService->isEnabled()) {
            return;
        }

        $item = $event->item;

        // Only handle incoming customer messages
        if (! $item->isFromCustomer() || $item->is_internal) {
            return;
        }

        // Skip if already assigned to a human
        $conversation = $item->conversation;

        if (! $conversation || $conversation->assignee_id !== null) {
            return;
        }

        // Skip if already escalated by the AI agent
        if (data_get($conversation->metadata, 'ai_agent_escalated')) {
            return;
        }

        try {
            $this->agentService->tryHandleConversation($conversation);
        } catch (\Throwable $e) {
            Log::error('HandleWithAiAgent: failed', [
                'conversation_id' => $conversation->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(ConversationMessageCreated $event, \Throwable $exception): void
    {
        Log::error('HandleWithAiAgent: job failed', [
            'item_id' => $event->item->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
