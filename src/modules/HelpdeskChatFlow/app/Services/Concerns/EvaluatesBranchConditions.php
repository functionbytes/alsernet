<?php

namespace Modules\HelpdeskChatFlow\Services\Concerns;

/**
 * Shared branch-condition evaluation used by BOTH the runtime ChatFlowEngine and
 * the ChatFlowTestSimulator, so test-cases evaluate conditions identically to
 * production (no operator drift). The engine resolves variable values from the
 * live session; the simulator from its in-memory context array — both supply a
 * `$resolve` callable, keeping this logic agnostic to the value source.
 */
trait EvaluatesBranchConditions
{
    /**
     * Evaluates a list of branch conditions under an AND ("all", default) or
     * OR ("any") combinator. An empty condition set always matches, preserving
     * the legacy fall-through behaviour of an else/unconditional branch.
     *
     * @param  array<int, array<string, mixed>>  $conditions
     * @param  callable(string): mixed  $resolve  Resolves a variable name to its actual value
     */
    private function evaluateConditions(array $conditions, string $match, callable $resolve): bool
    {
        if ($conditions === []) {
            return true;
        }

        $any = $match === 'any';

        foreach ($conditions as $cond) {
            $matches = $this->matchesCondition($resolve($cond['variable'] ?? ''), $cond);

            if ($any && $matches) {
                return true;
            }

            if (! $any && ! $matches) {
                return false;
            }
        }

        // "all": every condition matched. "any": none matched.
        return ! $any;
    }

    /**
     * Matches a single condition. The original operators (`=`, `!=`, `>`, `<`,
     * `contains`) keep their exact previous semantics; the rest are additive,
     * opt-in operators selected by the node data. Unknown operators fall
     * through to `false` as before.
     *
     * @param  array<string, mixed>  $cond
     */
    private function matchesCondition(mixed $actual, array $cond): bool
    {
        $value = $cond['value'] ?? '';

        return match ($cond['operator'] ?? '=') {
            '=' => (string) $actual === (string) $value,
            '!=' => (string) $actual !== (string) $value,
            '>' => (float) $actual > (float) $value,
            '<' => (float) $actual < (float) $value,
            'contains' => str_contains((string) $actual, (string) $value),
            '>=' => (float) $actual >= (float) $value,
            '<=' => (float) $actual <= (float) $value,
            'in' => $this->valueInList($actual, $value),
            'not_in' => ! $this->valueInList($actual, $value),
            'is_empty' => $this->isEmptyValue($actual),
            'not_empty' => ! $this->isEmptyValue($actual),
            'starts_with' => $value !== '' && str_starts_with((string) $actual, (string) $value),
            'ends_with' => $value !== '' && str_ends_with((string) $actual, (string) $value),
            'regex' => $this->matchesRegex((string) $actual, (string) $value),
            'between' => $this->isBetween($actual, $cond),
            default => false,
        };
    }

    /**
     * Membership test against a comma-separated string or an array of values.
     * Comparison is done on string casts to mirror the loose typing of the
     * context store.
     */
    private function valueInList(mixed $actual, mixed $value): bool
    {
        $list = is_array($value)
            ? $value
            : array_map('trim', explode(',', (string) $value));

        return in_array((string) $actual, array_map('strval', $list), true);
    }

    private function isEmptyValue(mixed $actual): bool
    {
        if (is_array($actual)) {
            return $actual === [];
        }

        return $actual === null || trim((string) $actual) === '';
    }

    /**
     * Regex match with a safety guard: the pattern is supplied without
     * delimiters and wrapped here. An invalid pattern or a match that exceeds
     * PCRE's backtrack/recursion limits (catastrophic backtracking) yields
     * `false` (no-match) instead of throwing.
     */
    private function matchesRegex(string $actual, string $pattern): bool
    {
        if ($pattern === '') {
            return false;
        }

        $delimited = '/'.str_replace('/', '\\/', $pattern).'/u';

        set_error_handler(static fn (): bool => true);

        $result = 0;
        try {
            $result = preg_match($delimited, $actual);
        } finally {
            restore_error_handler();
        }

        return $result === 1;
    }

    /**
     * Numeric range test (inclusive). Bounds come from an array value,
     * a `value2` companion key, or a "min,max" comma string. Missing or
     * malformed bounds yield no-match.
     *
     * @param  array<string, mixed>  $cond
     */
    private function isBetween(mixed $actual, array $cond): bool
    {
        [$min, $max] = $this->resolveRange($cond);

        if ($min === null || $max === null) {
            return false;
        }

        $number = (float) $actual;

        return $number >= $min && $number <= $max;
    }

    /**
     * @param  array<string, mixed>  $cond
     * @return array{0: float|null, 1: float|null}
     */
    private function resolveRange(array $cond): array
    {
        $value = $cond['value'] ?? null;

        if (is_array($value) && count($value) >= 2) {
            return [(float) $value[0], (float) $value[1]];
        }

        if (isset($cond['value2']) && $value !== null && $value !== '') {
            return [(float) $value, (float) $cond['value2']];
        }

        if (is_string($value) && str_contains($value, ',')) {
            $parts = array_map('trim', explode(',', $value));

            if (count($parts) >= 2) {
                return [(float) $parts[0], (float) $parts[1]];
            }
        }

        return [null, null];
    }
}
