<?php

namespace Modules\HelpdeskChatFlow\Services;

use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;

/**
 * Launches proactive (outbound) bot conversations — abandoned-cart nudges,
 * post-sale follow-ups, appointment reminders — instead of only reacting to an
 * inbound message. The flow's start node fires immediately, and its messages
 * are delivered to the customer's channel by the usual bot dispatcher.
 */
class ChatFlowOutboundService
{
    public function __construct(
        private readonly ChatFlowEngine $engine,
    ) {}

    /**
     * Start an active flow proactively on a conversation, seeding any known
     * context (customer name, order number…). Returns null when the flow is not
     * active or the conversation already has a running session.
     *
     * @param  array<string, mixed>  $context
     */
    public function launch(ChatFlow $flow, Conversation $conversation, array $context = []): ?ChatFlowSession
    {
        if (! $flow->isActive()) {
            return null;
        }

        if ($this->engine->hasActiveSession($conversation)) {
            return null;
        }

        return $this->engine->start($flow, $conversation, 'outbound', $context);
    }
}
