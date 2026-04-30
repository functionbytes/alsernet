<?php

namespace Modules\Chat\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Chat\Events\ConversationAssigned;
use Modules\Chat\Events\ConversationCreated;
use Modules\Chat\Events\ConversationStatusChanged;
use Modules\Chat\Events\ConversationUpdated;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Services\Automation\AutomationRuleExecutor;

class SendAutomationRulesListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly AutomationRuleExecutor $executor
    ) {}

    public function handleConversationCreated(ConversationCreated $event): void
    {
        $this->run('conversation_created', $event->conversation);
    }

    public function handleConversationStatusChanged(ConversationStatusChanged $event): void
    {
        $this->run('conversation_status_changed', $event->conversation);
    }

    public function handleConversationAssigned(ConversationAssigned $event): void
    {
        $this->run('assignee_changed', $event->conversation);
    }

    public function handleConversationUpdated(ConversationUpdated $event): void
    {
        $this->run($event->action, $event->conversation);
    }

    private function run(string $eventName, Conversation $conversation): void
    {
        try {
            $this->executor->execute($eventName, $conversation);
        } catch (\Throwable $e) {
            Log::error("Automation rule execution failed for [{$eventName}]", [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
