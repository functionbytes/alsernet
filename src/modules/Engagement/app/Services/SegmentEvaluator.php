<?php

namespace Modules\Engagement\Services;

use Modules\Engagement\Models\Event;
use Modules\Engagement\Models\Segment;
use Modules\Engagement\Models\VisitorContext;
use Modules\Engagement\Models\VisitorScore;

class SegmentEvaluator
{
    public function matches(Segment $segment, string $sessionToken, int $inboxId): bool
    {
        $conditions = $segment->conditions;
        $operator = $conditions['operator'] ?? 'AND';
        $rules = $conditions['rules'] ?? [];

        if (empty($rules)) {
            return false;
        }

        $results = [];

        foreach ($rules as $rule) {
            $results[] = $this->evaluateRule($rule, $sessionToken, $inboxId);
        }

        return $operator === 'AND'
            ? ! in_array(false, $results, true)
            : in_array(true, $results, true);
    }

    private function evaluateRule(array $rule, string $sessionToken, int $inboxId): bool
    {
        $field = $rule['field'] ?? '';
        $op = $rule['operator'] ?? 'eq';
        $value = $rule['value'] ?? null;

        $actual = match ($field) {
            'score' => $this->getScore($sessionToken, $inboxId),
            'segment' => $this->getSegment($sessionToken, $inboxId),
            'event_count' => $this->getEventCount($rule['event_name'] ?? '', $sessionToken, $inboxId, $rule['hours'] ?? 24),
            'has_event' => $this->hasEvent($rule['event_name'] ?? '', $sessionToken, $inboxId, $rule['hours'] ?? 24),
            'country' => $this->getContextField($sessionToken, 'country'),
            'city' => $this->getContextField($sessionToken, 'city'),
            'language' => $this->getContextField($sessionToken, 'language'),
            default => null,
        };

        return match ($op) {
            'eq' => $actual == $value,
            'ne' => $actual != $value,
            'gt' => is_numeric($actual) && is_numeric($value) && $actual > $value,
            'gte' => is_numeric($actual) && is_numeric($value) && $actual >= $value,
            'lt' => is_numeric($actual) && is_numeric($value) && $actual < $value,
            'lte' => is_numeric($actual) && is_numeric($value) && $actual <= $value,
            'contains' => is_string($actual) && is_string($value) && str_contains($actual, $value),
            'in' => is_array($value) && in_array($actual, $value, true),
            default => false,
        };
    }

    private function getScore(string $sessionToken, int $inboxId): ?int
    {
        $score = VisitorScore::query()
            ->forSession($sessionToken)
            ->where('inbox_id', $inboxId)
            ->first();

        return $score?->score;
    }

    private function getSegment(string $sessionToken, int $inboxId): ?string
    {
        $score = VisitorScore::query()
            ->forSession($sessionToken)
            ->where('inbox_id', $inboxId)
            ->first();

        return $score?->segment;
    }

    private function getEventCount(string $eventName, string $sessionToken, int $inboxId, int $hours): int
    {
        return Event::query()
            ->forSession($sessionToken)
            ->forInbox($inboxId)
            ->ofType($eventName)
            ->where('occurred_at', '>=', now()->subHours($hours))
            ->count();
    }

    private function hasEvent(string $eventName, string $sessionToken, int $inboxId, int $hours): bool
    {
        return Event::query()
            ->forSession($sessionToken)
            ->forInbox($inboxId)
            ->ofType($eventName)
            ->where('occurred_at', '>=', now()->subHours($hours))
            ->exists();
    }

    private function getContextField(string $sessionToken, string $field): ?string
    {
        $context = VisitorContext::query()
            ->forSession($sessionToken)
            ->first();

        return $context?->{$field};
    }
}
