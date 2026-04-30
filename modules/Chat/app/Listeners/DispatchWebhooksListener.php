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
use Modules\Chat\Services\Webhooks\WebhookDispatcher;

class DispatchWebhooksListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly WebhookDispatcher $dispatcher
    ) {}

    public function handleConversationCreated(ConversationCreated $event): void
    {
        $this->dispatch('conversation_created', $event->conversation);
    }

    public function handleConversationStatusChanged(ConversationStatusChanged $event): void
    {
        $this->dispatch('conversation_status_changed', $event->conversation);
    }

    public function handleConversationAssigned(ConversationAssigned $event): void
    {
        $this->dispatch('assignee_changed', $event->conversation);
    }

    public function handleConversationUpdated(ConversationUpdated $event): void
    {
        $this->dispatch($event->action, $event->conversation);
    }

    private function dispatch(string $event, Conversation $conversation): void
    {
        try {
            $this->dispatcher->dispatch($event, $conversation);
        } catch (\Throwable $e) {
            Log::error("Webhook dispatch failed for [{$event}]", [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
