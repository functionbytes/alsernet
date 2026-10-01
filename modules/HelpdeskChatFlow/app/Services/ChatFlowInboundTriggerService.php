<?php

namespace Modules\HelpdeskChatFlow\Services;

use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Services\AgentPresenceService;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;

/**
 * Starts a flow from an inbound customer message when the conversation has no
 * active session, using the message-driven triggers (keyword, intent, no_agent).
 *
 * conversation_start is NOT handled here (it only applies to the first message
 * and lives in ExecuteChatFlowNodeJob). `procedure` flows are never started from
 * an inbound message: they are only launched explicitly from other flows.
 *
 * Order of precedence: keyword > intent > no_agent.
 */
class ChatFlowInboundTriggerService
{
    /** @var array<int, string> */
    public const TRIGGER_ORDER = ['keyword', 'intent', 'no_agent'];

    public function __construct(
        private readonly ChatFlowEngine $engine,
        private readonly ChatFlowTriggerResolver $resolver,
    ) {}

    public function triggerFromMessage(Conversation $conversation, string $message): ?ChatFlowSession
    {
        $message = trim($message);

        if ($message === '' || ! $this->conversationIsBotEligible($conversation)) {
            return null;
        }

        $context = ['message' => $message];
        $seed = ['first_message' => $message, 'last_input' => $message];

        foreach (self::TRIGGER_ORDER as $triggerType) {
            $flow = $this->resolver->resolve($conversation, $triggerType, $context);

            if ($flow === null || ! $this->canLaunch($flow, $triggerType, $conversation)) {
                continue;
            }

            $variant = $this->engine->pickAbVariant($flow, $conversation);

            if ($this->endedRecently([$flow->id, $variant->id], $conversation->id)) {
                continue;
            }

            $session = $this->engine->start($variant, $conversation, $triggerType, $seed);

            if ($session !== null) {
                return $session;
            }
        }

        return null;
    }

    /**
     * Only unassigned conversations that are not already flagged as bot-handled
     * (an active session was ruled out by the caller) may start a flow: an
     * assigned human agent always wins.
     */
    protected function conversationIsBotEligible(Conversation $conversation): bool
    {
        if ($conversation->assignee_id !== null) {
            return false;
        }

        return ! ($conversation->metadata['handled_by_bot'] ?? false);
    }

    private function canLaunch(ChatFlow $flow, string $triggerType, Conversation $conversation): bool
    {
        if ($flow->trigger_type !== $triggerType) {
            return false;
        }

        if ($triggerType !== 'no_agent') {
            return true;
        }

        return ! $this->inboxHasAvailableAgents((int) $conversation->inbox_id);
    }

    protected function inboxHasAvailableAgents(int $inboxId): bool
    {
        return app(AgentPresenceService::class)->hasAvailableAgentsForInbox($inboxId);
    }

    /**
     * Anti-loop: the same flow is not relaunched in a conversation until the
     * cooldown (minutes) since its last session ended has elapsed.
     *
     * @param  array<int, int>  $flowIds
     */
    protected function endedRecently(array $flowIds, int $conversationId): bool
    {
        $minutes = (int) config('helpdeskchatflow.triggers.cooldown_minutes', 30);

        if ($minutes <= 0) {
            return false;
        }

        return ChatFlowSession::query()
            ->where('conversation_id', $conversationId)
            ->whereIn('chat_flow_id', array_unique($flowIds))
            ->where('ended_at', '>=', now()->subMinutes($minutes))
            ->exists();
    }
}
