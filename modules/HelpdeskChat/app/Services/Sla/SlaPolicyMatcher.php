<?php

namespace Modules\HelpdeskChat\Services\Sla;

use App\Models\SlaPolicy;
use Modules\HelpdeskChat\Models\Conversations\Conversation;

class SlaPolicyMatcher
{
    /**
     * Find the applicable SLA policy for a conversation.
     *
     * Currently uses a simple "first active policy" approach.
     * In the future, this can be extended to support conditions
     * like inbox, priority, labels, etc.
     */
    public function findApplicablePolicy(Conversation $conversation): ?SlaPolicy
    {
        // Get the first active SLA policy for this account
        return SlaPolicy::forAccount($conversation->account_id)
            ->active()
            ->first();
    }

    /**
     * Check if a conversation should have an SLA policy applied.
     */
    public function shouldApplySla(Conversation $conversation): bool
    {
        // Don't apply SLA to resolved conversation
        if ($conversation->isResolved()) {
            return false;
        }

        // Check if there's an active policy
        $policy = $this->findApplicablePolicy($conversation);

        return $policy !== null;
    }

    /**
     * Apply SLA policy to a conversation.
     * This updates the conversation's sla_policy_id field.
     */
    public function applyPolicy(Conversation $conversation): bool
    {
        $policy = $this->findApplicablePolicy($conversation);

        if (! $policy) {
            return false;
        }

        $conversation->update([
            'sla_policy_id' => $policy->id,
        ]);

        return true;
    }
}
