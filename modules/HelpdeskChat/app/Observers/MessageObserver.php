<?php

namespace Modules\HelpdeskChat\Observers;

use App\Models\User;
use Modules\HelpdeskChat\Contracts\MessageType;
use Modules\HelpdeskChat\Events\MessageSent;
use Modules\HelpdeskChat\Models\Conversations\ConversationMessage;
use Modules\HelpdeskChat\Services\Automation\AutomationRuleExecutor;
use Modules\HelpdeskChat\Services\SlaService;
use Modules\HelpdeskChat\Services\Webhooks\WebhookDispatcher;

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
        $this->webhookDispatcher->dispatch('message_created', $message);

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
            $this->slaService->checkFirstResponse($message->conversation);
        }

        // Skip automation for private notes if needed
        if ($message->private) {
            return;
        }

        // Trigger automation based on message type
        if ($message->message_type === MessageType::INCOMING) {
            $this->executor->execute('message_created', $message->conversation);
        }
    }

    /**
     * Handle the ConversationMessage "updated" event.
     */
    public function updated(Message $message): void
    {
        // Optionally trigger automation on message updates
        // For example, when message status changes (delivered, read, etc.)
    }
}
