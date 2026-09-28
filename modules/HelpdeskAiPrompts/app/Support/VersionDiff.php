<?php

namespace Modules\HelpdeskAiPrompts\Support;

/**
 * Simple field-level diff between an old version snapshot and the record's
 * current state, for the history screen. Not a line-by-line text diff —
 * just "this field changed, here is before/after" per top-level attribute.
 */
class VersionDiff
{
    private const IGNORED_FIELDS = ['id', 'created_at', 'updated_at', 'version', 'updated_by'];

    /**
     * @param  array<string, mixed>  $snapshot
     * @param  array<string, mixed>  $current
     * @return array<int, array{field: string, before: string, after: string}>
     */
    public static function changedFields(array $snapshot, array $current): array
    {
        $changes = [];

        foreach (array_unique([...array_keys($snapshot), ...array_keys($current)]) as $key) {
            if (in_array($key, self::IGNORED_FIELDS, true)) {
                continue;
            }

            $before = self::stringify($snapshot[$key] ?? null);
            $after = self::stringify($current[$key] ?? null);

            if ($before !== $after) {
                $changes[] = ['field' => $key, 'before' => self::truncate($before), 'after' => self::truncate($after)];
            }
        }

        return $changes;
    }

    private static function stringify(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
        }

        return (string) ($value ?? '');
    }

    private static function truncate(string $value, int $length = 200): string
    {
        return mb_strlen($value) > $length ? mb_substr($value, 0, $length).'…' : $value;
    }
}
