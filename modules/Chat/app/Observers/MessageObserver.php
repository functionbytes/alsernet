<?php

namespace Modules\Chat\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Modules\Chat\Contracts\MessageType;
use Modules\Chat\Events\MessageSent;
use Modules\Chat\Models\Conversations\ConversationMessage;
use Modules\Chat\Services\Automation\AutomationRuleExecutor;
use Modules\Chat\Services\SlaService;
use Modules\Chat\Services\Webhooks\WebhookDispatcher;

class MessageObserver
{
    public function __construct(
        protected AutomationRuleExecutor $executor,
        protected WebhookDispatcher $webhookDispatcher,
        protected SlaService $slaService
    ) {}

    /**
     * Handle the ConversationMessage "created" event.
     */
    public function created(ConversationMessage $message): void
    {
        // Always send webhook for message_created
        try {
            $this->webhookDispatcher->dispatch('message_created', $message);
        } catch (\Throwable $e) {
            Log::error('Failed to dispatch message_created webhook', [
                'message_id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // Broadcast message in real-time (except if created by ActivityMessageJob which does its own broadcast)
        if ($message->message_type !== MessageType::ACTIVITY) {
            // Load relationships for broadcast
            $message->load('sender', 'media');

            // Only use toOthers() for outgoing messages from authenticated users
            // Webhook-created messages (incoming) need full broadcast since there's no auth context
            if ($message->message_type === MessageType::OUTGOING && auth()->check()) {
                broadcast(new MessageSent($message))->toOthers();
            } else {
                broadcast(new MessageSent($message));
            }
        }

        // Check SLA first response when agent sends outgoing message
        if ($message->message_type === MessageType::OUTGOING && $message->sender_type === User::class) {
            try {
                $this->slaService->checkFirstResponse($message->conversation);
            } catch (\Throwable $e) {
                Log::error('Failed to check SLA first response', [
                    'message_id' => $message->id,
                    'conversation_id' => $message->conversation_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        // Skip automation for private notes if needed
        if ($message->private) {
            return;
        }

        // Trigger automation based on message type
        if ($message->message_type === MessageType::INCOMING) {
            try {
                $this->executor->execute('message_created', $message->conversation);
            } catch (\Throwable $e) {
                Log::error('Failed to execute message_created automation', [
                    'message_id' => $message->id,
                    'conversation_id' => $message->conversation_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
    }

    /**
     * Handle the ConversationMessage "updated" event.
     */
    public function updated(ConversationMessage $message): void
    {
        // Optionally trigger automation on message updates
        // For example, when message status changes (delivered, read, etc.)
    }
}
