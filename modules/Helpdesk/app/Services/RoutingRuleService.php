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

            if (! $rule->assign_to_user_id && ! $rule->assign_to_team_id) {
                continue;
            }

            try {
                if ($rule->assign_to_user_id) {
                    $conv->assignTo($rule->assign_to_user_id);
                }

                if ($rule->assign_to_team_id) {
                    $conv->assignToGroup($rule->assign_to_team_id);
                }
            } catch (\Throwable $e) {
                Log::error('RoutingRuleService: failed to assign conversation', [
                    'conversation_id' => $conv->id,
                    'rule_id' => $rule->id,
                    'error' => $e->getMessage(),
                ]);

                return false;
            }

            return true;
        }

        return false;
    }
}
