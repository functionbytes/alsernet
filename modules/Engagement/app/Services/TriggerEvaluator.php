<?php

namespace Modules\Engagement\Services;

use Modules\Engagement\Models\TriggerRule;
use Modules\Engagement\Models\VisitorContext;
use Modules\Engagement\Models\VisitorScore;

class TriggerEvaluator
{
    public function __construct(
        private readonly VariantAssigner $variantAssigner = new VariantAssigner,
    ) {}

    public function evaluate(string $sessionToken, int $inboxId): array
    {
        $rules = TriggerRule::query()
            ->forInbox($inboxId)
            ->active()
            ->ordered()
            ->get();

        // A/B testing: pick one rule per variant_group deterministically by session
        $rules = $this->variantAssigner->filter($rules, $sessionToken);

        $score = VisitorScore::query()->forSession($sessionToken)->first();
        $context = VisitorContext::query()->forSession($sessionToken)->first();

        $fired = [];

        foreach ($rules as $rule) {
            if ($this->matches($rule->conditions, $score, $context)) {
                $fired[] = [
                    'id' => $rule->id,
                    'action' => $rule->action,
                ];
            }
        }

        return $fired;
    }

    private function matches(array $conditions, ?VisitorScore $score, ?VisitorContext $context): bool
    {
        $operator = $conditions['operator'] ?? 'AND';
        $rules = $conditions['rules'] ?? [];

        if (empty($rules)) {
            return false;
        }

        $results = array_map(
            fn (array $rule) => $this->evaluateRule($rule, $score, $context),
            $rules,
        );

        if ($operator === 'OR') {
            return in_array(true, $results, true);
        }

        return ! in_array(false, $results, true);
    }

    private function evaluateRule(array $rule, ?VisitorScore $score, ?VisitorContext $context): bool
    {
        $type = $rule['type'] ?? '';
        $op = $rule['operator'] ?? '>=';
        $value = $rule['value'] ?? null;

        return match ($type) {
            'score' => $this->compare($score?->score ?? 0, $op, (int) $value),
            'segment' => ($score?->segment ?? 'cold') === $value,
            'context' => $this->compare(
                data_get($context?->context ?? [], $rule['key'] ?? '', 0),
                $op,
                $value,
            ),
            default => false,
        };
    }

    private function compare(mixed $actual, string $operator, mixed $expected): bool
    {
        return match ($operator) {
            '>=' => $actual >= $expected,
            '>' => $actual > $expected,
            '<=' => $actual <= $expected,
            '<' => $actual < $expected,
            '==' => $actual == $expected,
            '!=' => $actual != $expected,
            'contains' => str_contains((string) $actual, (string) $expected),
            default => false,
        };
    }
}
