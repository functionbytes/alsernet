<?php

namespace Modules\HelpdeskChatFlow\Services\Support;

/**
 * Reads values from a session context by name or dot path.
 *
 * `customer_email` reads a flat key (always checked first, so keys that
 * contain a dot keep working). `pedido.estado` walks nested arrays and also
 * JSON strings, which is how http_request nodes save their responses. Numeric
 * segments index lists (`pedido.lineas.0.sku`).
 */
final class ContextPath
{
    /** A variable name or a dot path: `a`, `a.b`, `a.0.b`. */
    public const REFERENCE = '\w+(?:\.\w+)*';

    /**
     * @param  array<string, mixed>  $context
     */
    public static function get(array $context, string $path, mixed $default = null): mixed
    {
        if (array_key_exists($path, $context)) {
            return $context[$path] ?? $default;
        }

        if (! str_contains($path, '.')) {
            return $default;
        }

        $segments = explode('.', $path);
        $current = $context;

        foreach ($segments as $segment) {
            $current = self::decode($current);

            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return $default;
            }

            $current = $current[$segment];
        }

        return $current ?? $default;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function has(array $context, string $path): bool
    {
        return self::get($context, $path) !== null;
    }

    /**
     * Whether the path holds something usable: not null, not blank text and
     * not an empty list.
     *
     * @param  array<string, mixed>  $context
     */
    public static function isFilled(array $context, string $path): bool
    {
        $value = self::get($context, $path);

        return match (true) {
            $value === null => false,
            is_array($value) => $value !== [],
            is_string($value) => trim($value) !== '',
            default => true,
        };
    }

    /**
     * Replaces every `{{name}}` / `{{a.b.c}}` with its value. Unresolvable
     * references stay literal, as flat variables always did.
     *
     * @param  array<string, mixed>  $context
     */
    public static function interpolate(string $text, array $context): string
    {
        if (! str_contains($text, '{{')) {
            return $text;
        }

        return preg_replace_callback(
            '/\{\{('.self::REFERENCE.')\}\}/',
            fn (array $m): string => self::stringify(self::get($context, $m[1])) ?? $m[0],
            $text,
        ) ?? $text;
    }

    /**
     * Root variable of a reference: `pedido.estado` -> `pedido`.
     */
    public static function root(string $path): string
    {
        return explode('.', $path, 2)[0];
    }

    private static function stringify(mixed $value): ?string
    {
        return match (true) {
            $value === null => null,
            is_bool($value) => $value ? '1' : '',
            is_scalar($value) => (string) $value,
            default => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null,
        };
    }

    private static function decode(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $trimmed = ltrim($value);

        if ($trimmed === '' || ! in_array($trimmed[0], ['{', '['], true)) {
            return $value;
        }

        $decoded = json_decode($trimmed, true);

        return is_array($decoded) ? $decoded : $value;
    }
}
