<?php

namespace Modules\HelpdeskAgents\Listeners;

use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Events\MessageReceived;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\HelpdeskAgents\Jobs\StartAiAgentSessionJob;
use Modules\HelpdeskAgents\Models\AiAgent;
use Modules\HelpdeskAgents\Models\AiAgentSession;

/**
 * Bridges the Helpdesk core conversation flow into the AI agent runtime.
 *
 * Reacts to the core MessageReceived event and dispatches StartAiAgentSessionJob
 * only when every safety condition holds. The runtime is OFF by default: with the
 * feature flag disabled (the production default) this listener fires but does
 * nothing, so configuring the module never silently starts answering customers.
 */
class StartAiAgentSessionOnIncomingMessage
{
    public function handle(MessageReceived $event): void
    {
        if (config('helpdeskagents.enabled') !== true) {
            return;
        }

        $conversation = $event->conversation;
        $message = $event->message;

        if (! $conversation instanceof Conversation || ! $message instanceof ConversationItem) {
            return;
        }

        if (! $this->isInboundCustomerMessage($message)) {
            return;
        }

        $agent = $this->resolveActiveAgent();

        if (! $agent) {
            return;
        }

        if ($this->hasRunningSession($conversation)) {
            return;
        }

        if (! $this->claimSessionStart($conversation)) {
            return;
        }

        StartAiAgentSessionJob::dispatch(
            $agent,
            $conversation,
            $conversation->customer,
            (string) $message->body
        );
    }

    /**
     * Only genuine inbound customer chat messages may start a session — never
     * agent replies, internal notes, system events, or bot output (avoids loops).
     */
    private function isInboundCustomerMessage(ConversationItem $message): bool
    {
        return $message->type === 'message'
            && ! $message->is_internal
            && $message->isFromCustomer();
    }

    /**
     * Resolve the AI agent that should answer. The schema has no per-inbox/channel
     * agent assignment, so this falls back to the active default agent (then any
     * active agent). Returns null when no active agent exists.
     */
    private function resolveActiveAgent(): ?AiAgent
    {
        return AiAgent::query()->active()->where('is_default', true)->first()
            ?? AiAgent::query()->active()->first();
    }

    /**
     * Idempotency guard: never start a second session while one is still active
     * for the same conversation.
     */
    private function hasRunningSession(Conversation $conversation): bool
    {
        return AiAgentSession::query()
            ->where('conversation_id', $conversation->id)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Atomic claim that covers the window between dispatch and the queued job
     * persisting its active session. The core broadcasts MessageReceived more
     * than once per item (link-preview observer + explicit event), so without
     * this two jobs could be queued in the same request. Cache::add is atomic
     * and returns false when the key already exists.
     */
    private function claimSessionStart(Conversation $conversation): bool
    {
        return Cache::add(
            'helpdeskagents:session-start:'.$conversation->id,
            true,
            now()->addMinutes(5)
        );
    }
}
