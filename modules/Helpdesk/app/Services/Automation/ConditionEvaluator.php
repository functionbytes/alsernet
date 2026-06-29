<?php

namespace Modules\Helpdesk\Services\Automation;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Pure evaluator: receives conditions JSON + context, returns bool.
 * Has no side-effects and does not touch the database.
 */
class ConditionEvaluator
{
    /**
     * Evaluate the full conditions block against the given context.
     *
     * @param  array<string, mixed>  $conditions  { operator: 'AND'|'OR', rules: [...] }
     * @param  array<string, mixed>  $context  { conversation, customer, inbox, message }
     */
    public function evaluate(array $conditions, array $context): bool
    {
        if (empty($conditions) || empty($conditions['rules'])) {
            return true;
        }

        $operator = strtoupper($conditions['operator'] ?? 'AND');
        $rules = $conditions['rules'] ?? [];

        if ($operator === 'OR') {
            foreach ($rules as $rule) {
                if ($this->evaluateRule($rule, $context)) {
                    return true;
                }
            }

            return false;
        }

        // Default: AND
        foreach ($rules as $rule) {
            if (! $this->evaluateRule($rule, $context)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate a single rule against the context.
     *
     * @param  array<string, mixed>  $rule  { type, operator, value }
     * @param  array<string, mixed>  $context
     */
    protected function evaluateRule(array $rule, array $context): bool
    {
        $type = $rule['type'] ?? null;
        $operator = $rule['operator'] ?? 'equals';
        $expected = $rule['value'] ?? null;

        if (! $type) {
            return false;
        }

        try {
            $actual = $this->resolveContextValue($type, $context);
        } catch (\Throwable) {
            return false;
        }

        // Missing context value → condition not met
        if ($actual === null && ! in_array($operator, ['is_null', 'is_not_null'], true)) {
            return false;
        }

        return $this->applyOperator($actual, $operator, $expected);
    }

    /**
     * Extract the relevant value from context for the given condition type.
     *
     * @param  array<string, mixed>  $context
     */
    protected function resolveContextValue(string $type, array $context): mixed
    {
        $conversation = $context['conversation'] ?? null;
        $customer = $context['customer'] ?? null;
        $inbox = $context['inbox'] ?? null;
        $message = $context['message'] ?? null;

        return match ($type) {
            'status' => $this->resolveStatus($conversation),
            'priority' => $conversation?->priority,
            'channel_type' => $conversation?->channel ?? $inbox?->channel_type,
            'inbox_id' => $conversation?->inbox_id,
            'assignee_id' => $conversation?->assignee_id,
            'team_id', 'group_id' => $conversation?->group_id,
            'labels' => $this->resolveLabelIds($conversation),
            'content' => $message?->body ?? ($conversation ? $this->resolveLastMessageBody($conversation) : null),
            'email' => $customer?->email,
            'country_code' => $customer?->country,
            'browser_language' => $this->resolveAttribute($customer, 'language') ?? $this->resolveAttribute($context['session'] ?? null, 'browser_language'),
            'referer' => $this->resolveAttribute($context['session'] ?? null, 'referer'),
            'created_within_minutes' => $conversation ? (int) Carbon::parse($conversation->created_at)->diffInMinutes(now()) : null,
            default => null,
        };
    }

    /**
     * Apply the operator comparing actual value to expected.
     */
    protected function applyOperator(mixed $actual, string $operator, mixed $expected): bool
    {
        return match ($operator) {
            'equals' => $actual == $expected,
            'not_equals' => $actual != $expected,
            'in' => in_array($actual, (array) $expected, false),
            'is_null' => $actual === null,
            'is_not_null' => $actual !== null,
            'contains' => $this->checkContains($actual, $expected),
            'not_contains' => ! $this->checkContains($actual, $expected),
            'regex' => $this->checkRegex($actual, (string) $expected),
            'starts_with' => is_string($actual) && str_starts_with($actual, (string) $expected),
            'ends_with' => is_string($actual) && str_ends_with($actual, (string) $expected),
            'lt' => is_numeric($actual) && $actual < $expected,
            'gt' => is_numeric($actual) && $actual > $expected,
            default => false,
        };
    }

    // ──────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────

    private function resolveStatus(mixed $conversation): ?string
    {
        if (! $conversation) {
            return null;
        }

        // Support both status slug and status_id
        if ($conversation->relationLoaded('status') && $conversation->status) {
            return $conversation->status->slug ?? $conversation->status->name;
        }

        return null;
    }

    /** @return array<int>|null */
    private function resolveLabelIds(mixed $conversation): ?array
    {
        if (! $conversation) {
            return null;
        }

        if ($conversation->relationLoaded('conversationTags')) {
            return $conversation->conversationTags->pluck('id')->all();
        }

        return null;
    }

    private function resolveLastMessageBody(mixed $conversation): ?string
    {
        if (! $conversation->relationLoaded('items')) {
            return null;
        }

        return $conversation->items
            ->where('type', 'message')
            ->last()
            ?->body;
    }

    private function resolveAttribute(mixed $model, string $key): mixed
    {
        if (! $model) {
            return null;
        }

        return $model->{$key} ?? ($model[$key] ?? null);
    }

    private function checkContains(mixed $actual, mixed $expected): bool
    {
        // Array: check if expected ID(s) are present
        if (is_array($actual)) {
            foreach ((array) $expected as $val) {
                if (in_array($val, $actual, false)) {
                    return true;
                }
            }

            return false;
        }

        return is_string($actual) && str_contains($actual, (string) $expected);
    }

    private function checkRegex(mixed $actual, string $pattern): bool
    {
        if (! is_string($actual)) {
            return false;
        }

        // Wrap bare patterns (no delimiters) to be safe
        if (! preg_match('/^[\/~!@#%\^&*\-+=|:;\.,\?]/', $pattern)) {
            $pattern = '/'.$pattern.'/i';
        }

        try {
            return (bool) preg_match($pattern, $actual);
        } catch (\Throwable $e) {
            Log::warning('AutomationEngine: invalid regex in condition', ['pattern' => $pattern, 'error' => $e->getMessage()]);

            return false;
        }
    }
}
