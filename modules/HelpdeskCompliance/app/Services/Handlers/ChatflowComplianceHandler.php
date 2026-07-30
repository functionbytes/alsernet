<?php

namespace Modules\HelpdeskCompliance\Services\Handlers;

use Modules\HelpdeskChatFlow\Models\ChatFlowExecution;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;

/**
 * Cascades a GDPR erasure to the chatbot sessions of the customer's conversations.
 * Soft delete sanitizes the session context (which can hold form-collected PII);
 * hard delete removes sessions and their executions. Sessions are located by the
 * conversation ids captured before deletion. Invoked only when HelpdeskChatFlow
 * is enabled (guarded by the listener).
 */
class ChatflowComplianceHandler
{
    /**
     * @param  array<int, int>  $conversationIds
     * @return array{module: string, sessions: int, mode: string}
     */
    public function handle(int $customerId, bool $hard, array $conversationIds): array
    {
        if ($conversationIds === []) {
            return ['module' => 'HelpdeskChatFlow', 'sessions' => 0, 'mode' => 'skipped'];
        }

        $sessionIds = ChatFlowSession::query()
            ->whereIn('conversation_id', $conversationIds)
            ->pluck('id');

        if ($sessionIds->isEmpty()) {
            return ['module' => 'HelpdeskChatFlow', 'sessions' => 0, 'mode' => 'skipped'];
        }

        if ($hard) {
            ChatFlowExecution::query()->whereIn('session_id', $sessionIds)->delete();
            ChatFlowSession::query()->whereKey($sessionIds->all())->delete();

            return ['module' => 'HelpdeskChatFlow', 'sessions' => $sessionIds->count(), 'mode' => 'deleted'];
        }

        ChatFlowSession::query()->whereKey($sessionIds->all())->update(['context' => null]);

        return ['module' => 'HelpdeskChatFlow', 'sessions' => $sessionIds->count(), 'mode' => 'sanitized'];
    }
}
