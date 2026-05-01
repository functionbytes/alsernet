<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\RoutingRule;

class RoutingRuleService
{
    /**
     * Evaluate active routing rules against the first item's body and assign
     * the conversation to the first matching rule's user/team.
     *
     * Returns true if at least one rule matched and an assignment was made.
     */
    public function matchAndAssign(Conversation $conv, ConversationItem $firstItem): bool
    {
        $body = trim((string) $firstItem->body);

        if ($body === '') {
            return false;
        }

        $rules = RoutingRule::query()
            ->where('is_active', true)
            ->orderBy('priority')
            ->orderBy('id')
            ->get();

        foreach ($rules as $rule) {
            if (! $rule->matches($body)) {
                continue;
            }

            $updates = [];

            if ($rule->assign_to_user_id) {
                $updates['assignee_id'] = $rule->assign_to_user_id;
                $updates['assigned_at'] = now();
            }

            if ($rule->assign_to_team_id) {
                $updates['group_id'] = $rule->assign_to_team_id;
            }

            if (empty($updates)) {
                continue;
            }

            try {
                $conv->update($updates);
            } catch (\Throwable $e) {
                Log::error('RoutingRuleService: failed to assign conversation', [
                    'conversation_id' => $conv->id,
                    'rule_id' => $rule->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return true;
        }

        return false;
    }
}
