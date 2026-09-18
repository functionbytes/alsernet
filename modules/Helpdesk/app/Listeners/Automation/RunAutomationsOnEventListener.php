<?php

namespace Modules\Helpdesk\Listeners\Automation;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationClosed;
use Modules\Helpdesk\Events\ConversationCreated;
use Modules\Helpdesk\Events\ConversationUpdated;
use Modules\Helpdesk\Events\MessageReceived;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Services\Automation\AutomationEngine;

class RunAutomationsOnEventListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'helpdesk-events';

    public int $tries = 3;

    public array $backoff = [5, 15, 30];

    public function __construct(
        private readonly AutomationEngine $engine,
    ) {}

    public function handleConversationCreated(ConversationCreated $event): void
    {
        $conversation = $this->withRelations($event->conversation);
        $this->run('conversation.created', $this->buildContext($conversation));
    }

    public function handleConversationUpdated(ConversationUpdated $event): void
    {
        $conversation = $this->withRelations($event->conversation);
        $this->run('conversation.updated', $this->buildContext($conversation));
    }

    public function handleConversationClosed(ConversationClosed $event): void
    {
        $conversation = $this->withRelations($event->conversation);
        $this->run('conversation.resolved', $this->buildContext($conversation));
    }

    public function handleMessageReceived(MessageReceived $event): void
    {
        $conversation = $this->withRelations($event->conversation);
        $context = $this->buildContext($conversation, $event->message);
        $this->run('message.created', $context);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('RunAutomationsOnEventListener failed', [
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function run(string $eventName, array $context): void
    {
        try {
            $this->engine->runFor($eventName, $context);
        } catch (\Throwable $e) {
            Log::error('AutomationEngine::runFor threw an exception', [
                'event' => $eventName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildContext(Conversation $conversation, ?ConversationItem $message = null): array
    {
        return [
            'conversation' => $conversation,
            'customer' => $conversation->relationLoaded('customer') ? $conversation->customer : null,
            'inbox' => $conversation->relationLoaded('inbox') ? $conversation->inbox : null,
            'message' => $message,
        ];
    }

    /**
     * Ensure the relations needed by ConditionEvaluator are loaded.
     */
    private function withRelations(Conversation $conversation): Conversation
    {
        $missing = array_filter(
            ['customer', 'inbox', 'status', 'conversationTags'],
            fn (string $rel) => ! $conversation->relationLoaded($rel)
        );

        if (! empty($missing)) {
            $conversation->load($missing);
        }

        return $conversation;
    }
}
