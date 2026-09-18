<?php

namespace Modules\HelpdeskSocial\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskSocial\Models\SocialAgentWorkload;
use Modules\HelpdeskSocial\Models\SocialAssignmentRule;
use Modules\HelpdeskSocial\Models\SocialComment;

class SmartAssignmentService
{
    /**
     * Clave de caché de las reglas de asignación activas.
     * Invalidada por SocialAssignmentRuleObserver ante cualquier save/delete.
     */
    public const RULES_CACHE_KEY = 'helpdesksocial:assignment-rules:active';

    private const CACHE_TTL = 900;

    /**
     * Evaluate all active assignment rules against a comment and apply the first match.
     */
    public function assign(SocialComment $comment): ?array
    {
        $rules = $this->activeRules();

        foreach ($rules as $rule) {
            if ($this->matches($comment, $rule)) {
                return $this->applyStrategy($comment, $rule);
            }
        }

        return null;
    }

    /**
     * Evaluate rule conditions against a comment.
     */
    public function matches(SocialComment $comment, SocialAssignmentRule $rule): bool
    {
        $conditions = $rule->conditions ?? [];

        if (empty($conditions)) {
            return true;
        }

        foreach ($conditions as $field => $expected) {
            $actual = match ($field) {
                'platform' => $comment->platform,
                'intent' => $comment->intent,
                'urgency' => $comment->urgency,
                'keywords' => $comment->body,
                default => null,
            };

            if (! $this->evaluateCondition($actual, $expected, $field)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Apply the assignment strategy defined by the rule.
     *
     * @return array<string, mixed>
     */
    public function applyStrategy(SocialComment $comment, SocialAssignmentRule $rule): array
    {
        $strategy = $rule->assignment_strategy ?? 'direct';

        return match ($strategy) {
            'direct' => $this->assignDirect($comment, $rule),
            'round_robin' => $this->assignRoundRobin($comment, $rule),
            'least_busy' => $this->assignLeastBusy($comment, $rule),
            default => $this->assignDirect($comment, $rule),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function assignDirect(SocialComment $comment, SocialAssignmentRule $rule): array
    {
        $userId = $rule->assignee_user_id;

        if (! $userId) {
            return [
                'assigned' => false,
                'reason' => 'No assignee configured for direct strategy',
            ];
        }

        $this->performAssignment($comment, $userId);

        return [
            'assigned' => true,
            'user_id' => $userId,
            'strategy' => 'direct',
            'rule_id' => $rule->id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function assignRoundRobin(SocialComment $comment, SocialAssignmentRule $rule): array
    {
        $eligibleUserIds = $this->resolveEligibleUserIds($rule);

        if (empty($eligibleUserIds)) {
            return [
                'assigned' => false,
                'reason' => 'No eligible agents for round_robin',
            ];
        }

        $lastAssigned = SocialAgentWorkload::query()
            ->whereIn('user_id', $eligibleUserIds)
            ->whereNotNull('last_assigned_at')
            ->orderByDesc('last_assigned_at')
            ->first();

        if ($lastAssigned) {
            $lastIndex = array_search($lastAssigned->user_id, $eligibleUserIds, true);
            $nextIndex = ($lastIndex !== false) ? ($lastIndex + 1) % count($eligibleUserIds) : 0;
        } else {
            $nextIndex = 0;
        }

        $userId = $eligibleUserIds[$nextIndex];
        $this->performAssignment($comment, $userId);

        return [
            'assigned' => true,
            'user_id' => $userId,
            'strategy' => 'round_robin',
            'rule_id' => $rule->id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function assignLeastBusy(SocialComment $comment, SocialAssignmentRule $rule): array
    {
        $eligibleUserIds = $this->resolveEligibleUserIds($rule);

        if (empty($eligibleUserIds)) {
            return [
                'assigned' => false,
                'reason' => 'No eligible agents for least_busy',
            ];
        }

        $workload = SocialAgentWorkload::query()
            ->whereIn('user_id', $eligibleUserIds)
            ->orderBy('active_assigned_count')
            ->orderBy('last_assigned_at')
            ->first();

        if ($workload) {
            $userId = $workload->user_id;
        } else {
            $userId = $eligibleUserIds[0];
        }

        $this->performAssignment($comment, $userId);

        return [
            'assigned' => true,
            'user_id' => $userId,
            'strategy' => 'least_busy',
            'rule_id' => $rule->id,
        ];
    }

    private function performAssignment(SocialComment $comment, int $userId): void
    {
        $comment->update(['assigned_to_user_id' => $userId]);

        // El contador active_assigned_count se mantiene de forma atómica en
        // SocialCommentObserver al detectar el cambio de assigned_to_user_id/status,
        // por lo que aquí solo registramos el momento de la última asignación.
        SocialAgentWorkload::updateOrCreate(
            ['user_id' => $userId],
            ['last_assigned_at' => now()]
        );

        Log::info('SmartAssignmentService: comment assigned', [
            'comment_id' => $comment->id,
            'user_id' => $userId,
        ]);
    }

    /**
     * Resolve eligible user IDs from a rule.
     *
     * @return array<int>
     */
    private function resolveEligibleUserIds(SocialAssignmentRule $rule): array
    {
        $conditions = $rule->conditions ?? [];
        $eligibleUsers = $conditions['eligible_users'] ?? null;

        if (is_array($eligibleUsers) && ! empty($eligibleUsers)) {
            return array_map('intval', $eligibleUsers);
        }

        if ($rule->assignee_user_id) {
            return [$rule->assignee_user_id];
        }

        return User::query()
            ->where('is_active', true)
            ->pluck('id')
            ->map('intval')
            ->all();
    }

    /**
     * @return Collection<int, SocialAssignmentRule>
     */
    private function activeRules(): Collection
    {
        return Cache::remember(
            self::RULES_CACHE_KEY,
            self::CACHE_TTL,
            fn () => SocialAssignmentRule::active()->ordered()->get()
        );
    }

    private function evaluateCondition(mixed $actual, mixed $expected, string $field): bool
    {
        if ($field === 'keywords') {
            if (! is_array($expected)) {
                return true;
            }

            $text = mb_strtolower((string) $actual);

            foreach ($expected as $keyword) {
                if (str_contains($text, mb_strtolower((string) $keyword))) {
                    return true;
                }
            }

            return false;
        }

        if (is_array($expected)) {
            return in_array($actual, $expected, true);
        }

        return $actual === $expected;
    }
}
