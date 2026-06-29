<?php

namespace Modules\Attention\Services;

use Illuminate\Support\Facades\Log;
use Modules\Attention\Models\Attention;
use Modules\Attention\Models\AttentionRoutingRule;

class AttentionRoutingService
{
    /**
     * Evaluates all active rules in priority order and applies the first match.
     *
     * Returns true if a rule was applied, false if no rule matched.
     */
    public function applyRules(Attention $attention): bool
    {
        $rules = AttentionRoutingRule::active()->get();

        foreach ($rules as $rule) {
            if (! $rule->matches($attention)) {
                continue;
            }

            $updates = $this->buildUpdates($rule);

            if (empty($updates)) {
                continue;
            }

            $attention->updateQuietly($updates);

            Log::info('Attention auto-assigned by routing rule', [
                'attention_id' => $attention->id,
                'radicado' => $attention->radicado,
                'rule_id' => $rule->id,
                'rule_name' => $rule->name,
                'updates' => $updates,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Builds the attribute updates from a matched rule.
     *
     * @return array<string, int>
     */
    private function buildUpdates(AttentionRoutingRule $rule): array
    {
        $updates = [];

        if ($rule->assign_to_user_id) {
            $updates['assigned_user_id'] = $rule->assign_to_user_id;
        }

        if ($rule->assign_to_department_id) {
            $updates['department_id'] = $rule->assign_to_department_id;
        }

        return $updates;
    }
}
