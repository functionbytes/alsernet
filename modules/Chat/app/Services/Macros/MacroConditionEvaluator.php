<?php

namespace Modules\Chat\Services\Macros;

use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Macro;

class MacroConditionEvaluator
{
    /**
     * Evaluate if a macro's conditions are met for a conversation
     */
    public function evaluate(Macro $macro, Conversation $conversation): bool
    {
        // If no conditions, always execute
        if (! $macro->conditions || empty($macro->conditions)) {
            return true;
        }

        // If macro is disabled, don't execute
        if (! $macro->enabled) {
            return false;
        }

        $conditions = $macro->conditions;

        // Handle different condition structures
        if (isset($conditions['operator'])) {
            return $this->evaluateConditionGroup($conditions, $conversation);
        }

        // Legacy: single condition
        return $this->evaluateSingleCondition($conditions, $conversation);
    }

    /**
     * Evaluate a group of conditions with AND/OR operators
     */
    protected function evaluateConditionGroup(array $conditionGroup, Conversation $conversation): bool
    {
        $operator = $conditionGroup['operator'] ?? 'and';
        $conditions = $conditionGroup['conditions'] ?? [];

        if (empty($conditions)) {
            return true;
        }

        $results = [];

        foreach ($conditions as $condition) {
            // Nested condition group
            if (isset($condition['operator'])) {
                $results[] = $this->evaluateConditionGroup($condition, $conversation);
            } else {
                $results[] = $this->evaluateSingleCondition($condition, $conversation);
            }
        }

        // Apply operator
        if ($operator === 'or') {
            return in_array(true, $results, true);
        }

        // Default to AND
        return ! in_array(false, $results, true);
    }

    /**
     * Evaluate a single condition
     */
    protected function evaluateSingleCondition(array $condition, Conversation $conversation): bool
    {
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? 'equals';
        $value = $condition['value'] ?? null;

        if (! $field) {
            return false;
        }

        $actualValue = $this->getFieldValue($field, $conversation);

        return $this->compareValues($actualValue, $operator, $value);
    }

    /**
     * Get field value from conversation
     */
    protected function getFieldValue(string $field, Conversation $conversation): mixed
    {
        return match ($field) {
            'status' => $conversation->status,
            'priority' => $conversation->priority,
            'assignee_id' => $conversation->assignee_id,
            'team_id' => $conversation->team_id,
            'inbox_id' => $conversation->inbox_id,
            'customer_id' => $conversation->customer_id,
            'labels' => $conversation->cached_label_list,
            'message_count' => $conversation->messages()->count(),
            'unread_count' => $conversation->unread_count ?? 0,
            'waiting_since' => $conversation->waiting_since?->diffInMinutes(now()),
            'created_hours_ago' => $conversation->created_at->diffInHours(now()),
            'contact_email' => $conversation->customer->email ?? null,
            'contact_phone' => $conversation->customer->phone_number ?? null,
            'inbox_name' => $conversation->inbox->name ?? null,
            'assignee_name' => $conversation->assignee->name ?? null,
            'team_name' => $conversation->team->name ?? null,
            default => null,
        };
    }

    /**
     * Compare values based on operator
     */
    protected function compareValues(mixed $actual, string $operator, mixed $expected): bool
    {
        return match ($operator) {
            'equals' => $actual == $expected,
            'not_equals' => $actual != $expected,
            'contains' => str_contains((string) $actual, (string) $expected),
            'not_contains' => ! str_contains((string) $actual, (string) $expected),
            'starts_with' => str_starts_with((string) $actual, (string) $expected),
            'ends_with' => str_ends_with((string) $actual, (string) $expected),
            'greater_than' => $actual > $expected,
            'greater_than_or_equals' => $actual >= $expected,
            'less_than' => $actual < $expected,
            'less_than_or_equals' => $actual <= $expected,
            'is_empty' => empty($actual),
            'is_not_empty' => ! empty($actual),
            'in' => in_array($actual, (array) $expected),
            'not_in' => ! in_array($actual, (array) $expected),
            default => false,
        };
    }
}
