<?php

namespace Modules\HelpdeskChatFlow\Services\Compliance;

use Modules\Helpdesk\Contracts\GdprExportContributor;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;

/**
 * GDPR right-of-access section for HelpdeskChatFlow: the chatbot sessions of
 * the customer's conversations, including the session context (which can hold
 * form-collected PII — the same field the deletion cascade sanitizes).
 */
class ChatflowGdprExportContributor implements GdprExportContributor
{
    public function sectionKey(): string
    {
        return 'chatflow_sessions';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function export(Customer $customer): array
    {
        $conversationIds = $customer->conversations()->withTrashed()->pluck('id');

        if ($conversationIds->isEmpty()) {
            return [];
        }

        return ChatFlowSession::query()
            ->whereIn('conversation_id', $conversationIds)
            ->with('chatFlow:id,name')
            ->orderBy('started_at')
            ->get()
            ->map(fn (ChatFlowSession $session): array => [
                'id' => $session->id,
                'conversation_id' => $session->conversation_id,
                'flow' => $session->chatFlow?->name,
                'status' => $session->status,
                'trigger_type' => $session->trigger_type,
                'context' => $session->context,
                'started_at' => $session->started_at?->toIso8601String(),
                'ended_at' => $session->ended_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }
}
