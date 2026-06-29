<?php

namespace Modules\HelpdeskChatFlow\Services;

use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskChatFlow\Models\ChatFlow;

class ChatFlowTriggerResolver
{
    /**
     * Finds the active ChatFlow that should fire for a conversation and trigger type.
     */
    public function resolve(Conversation $conversation, string $triggerType, array $context = []): ?ChatFlow
    {
        $query = ChatFlow::query()
            ->active()
            ->where('trigger_type', $triggerType)
            ->forInbox($conversation->inbox_id)
            ->orderByDesc('priority')
            ->orderBy('id');

        if ($triggerType === 'keyword' && isset($context['message'])) {
            return $query->get()->first(fn (ChatFlow $flow) => $this->matchesKeyword($flow, $context['message']));
        }

        return $query->first();
    }

    private function matchesKeyword(ChatFlow $flow, string $message): bool
    {
        $keywords = $flow->trigger_conditions['keywords'] ?? [];
        $message = strtolower(trim($message));

        foreach ($keywords as $keyword) {
            if (str_contains($message, strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }
}
