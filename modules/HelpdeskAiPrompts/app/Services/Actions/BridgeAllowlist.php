<?php

namespace Modules\HelpdeskAiPrompts\Services\Actions;

/**
 * Acceso tipado a config('ai-actions.bridge_allowlist'). Las claves contienen
 * puntos ("order.detail"), por eso no se usa la notación de puntos de config().
 */
class BridgeAllowlist
{
    /**
     * @return array{mode: string, customer: bool, order: bool|string}|null
     */
    public static function find(string $action): ?array
    {
        $list = (array) config('ai-actions.bridge_allowlist', []);

        if (! isset($list[$action]) || ! is_array($list[$action])) {
            return null;
        }

        return $list[$action] + ['mode' => 'read', 'customer' => true, 'order' => false];
    }

    /**
     * @return array<int, string>
     */
    public static function actions(): array
    {
        return array_keys((array) config('ai-actions.bridge_allowlist', []));
    }

    public static function isWrite(string $action): bool
    {
        return (self::find($action)['mode'] ?? 'read') === 'write';
    }
}
