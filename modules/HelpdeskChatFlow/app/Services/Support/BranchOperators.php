<?php

namespace Modules\HelpdeskChatFlow\Services\Support;

/**
 * Operators understood by EvaluatesBranchConditions, and the kind of value
 * each one expects. The editor (BranchItemConfig.tsx) mirrors this table.
 */
final class BranchOperators
{
    public const KIND_NONE = 'none';

    public const KIND_TEXT = 'text';

    public const KIND_NUMBER = 'number';

    public const KIND_RANGE = 'range';

    public const KIND_LIST = 'list';

    public const KIND_REGEX = 'regex';

    /** @var array<string, string> operator => value kind */
    public const KINDS = [
        '=' => self::KIND_TEXT,
        '!=' => self::KIND_TEXT,
        '>' => self::KIND_NUMBER,
        '<' => self::KIND_NUMBER,
        '>=' => self::KIND_NUMBER,
        '<=' => self::KIND_NUMBER,
        'contains' => self::KIND_TEXT,
        'starts_with' => self::KIND_TEXT,
        'ends_with' => self::KIND_TEXT,
        'in' => self::KIND_LIST,
        'not_in' => self::KIND_LIST,
        'is_empty' => self::KIND_NONE,
        'not_empty' => self::KIND_NONE,
        'between' => self::KIND_RANGE,
        'regex' => self::KIND_REGEX,
    ];

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return array_keys(self::KINDS);
    }

    public static function isValid(string $operator): bool
    {
        return isset(self::KINDS[$operator]);
    }

    /**
     * Why a condition's value is unusable for its operator, or null when fine.
     * Accepts the value as the editor stores it (string, or array for
     * list/range) and as the engine tolerates it (comma-separated string,
     * `value2` companion for ranges).
     *
     * @param  array<string, mixed>  $condition
     */
    public static function valueProblem(array $condition): ?string
    {
        $operator = (string) ($condition['operator'] ?? '=');
        $kind = self::KINDS[$operator] ?? null;
        $value = $condition['value'] ?? '';

        return match ($kind) {
            null, self::KIND_NONE => null,
            self::KIND_TEXT => self::isBlank($value) ? 'falta el valor' : null,
            self::KIND_NUMBER => is_numeric(is_string($value) ? trim($value) : $value) ? null : 'el valor debe ser un número',
            self::KIND_LIST => self::listItems($value) === [] ? 'la lista de valores está vacía' : null,
            self::KIND_RANGE => self::rangeProblem($condition),
            self::KIND_REGEX => is_string($value) && trim($value) !== ''
                ? SafeRegex::problem($value)
                : 'falta la expresión regular',
        };
    }

    /**
     * @return array<int, string>
     */
    private static function listItems(mixed $value): array
    {
        $items = is_array($value) ? $value : explode(',', (string) $value);

        return array_values(array_filter(
            array_map(fn ($item) => is_scalar($item) ? trim((string) $item) : '', $items),
            fn (string $item) => $item !== '',
        ));
    }

    /**
     * @param  array<string, mixed>  $condition
     */
    private static function rangeProblem(array $condition): ?string
    {
        $value = $condition['value'] ?? null;

        if (is_array($value)) {
            $bounds = array_values($value);
        } elseif (isset($condition['value2'])) {
            $bounds = [$value, $condition['value2']];
        } else {
            $bounds = is_string($value) ? array_map('trim', explode(',', $value)) : [];
        }

        if (count($bounds) < 2 || ! is_numeric($bounds[0] ?? null) || ! is_numeric($bounds[1] ?? null)) {
            return 'el rango necesita dos valores numéricos (mínimo y máximo)';
        }

        return (float) $bounds[0] > (float) $bounds[1] ? 'el mínimo del rango es mayor que el máximo' : null;
    }

    private static function isBlank(mixed $value): bool
    {
        return $value === null || (is_scalar($value) ? trim((string) $value) === '' : $value === []);
    }
}
