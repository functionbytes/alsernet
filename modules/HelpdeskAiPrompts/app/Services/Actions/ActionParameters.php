<?php

namespace Modules\HelpdeskAiPrompts\Services\Actions;

/**
 * Parámetros EFECTIVOS de una acción: los declarados en el panel más los que el
 * sistema añade según las reglas (pedido+email, confirmación). Los usa tanto el
 * registro (para construir la herramienta) como el ejecutor (para validar), de
 * modo que lo que se le ofrece a la IA y lo que se acepta nunca divergen.
 */
class ActionParameters
{
    public const CONFIRM = 'customer_confirmed';

    public const ORDER_REF = 'order_ref';

    public const EMAIL = 'email';

    /**
     * @param  array<string, mixed>  $action  type, config, rules, parameters
     * @return array<int, array<string, mixed>>
     */
    public static function effective(array $action, bool $verified): array
    {
        $auto = [];

        if (self::needsOrderRef($action)) {
            $auto[self::ORDER_REF] = [
                'name' => self::ORDER_REF,
                'type' => 'string',
                'description' => 'Número o referencia del pedido, tal como lo da el cliente',
                'required' => true,
                'pattern' => '^[A-Za-z0-9._\-]{1,40}$',
                'max_length' => 40,
            ];
        }

        if (self::ownership($action) === 'order_email_pair' && ! $verified) {
            $auto[self::EMAIL] = [
                'name' => self::EMAIL,
                'type' => 'email',
                'description' => 'Email con el que se hizo la compra, tal como lo escribe el cliente',
                'required' => true,
            ];
        }

        if (self::needsConfirmation($action)) {
            $auto[self::CONFIRM] = [
                'name' => self::CONFIRM,
                'type' => 'boolean',
                'description' => 'true solo si el cliente ha pedido o confirmado expresamente esta acción en su último mensaje; si no, pregúntale antes y no la ejecutes',
                'required' => true,
            ];
        }

        $declared = [];
        foreach ((array) ($action['parameters'] ?? []) as $param) {
            if (is_array($param) && isset($param['name']) && ! isset($auto[$param['name']])) {
                $declared[] = $param;
            }
        }

        return array_merge($declared, array_values($auto));
    }

    /**
     * @param  array<string, mixed>  $action
     * @return array<int, string>
     */
    public static function names(array $action): array
    {
        return array_map(
            fn (array $p): string => $p['name'],
            [...self::effective($action, false), ...self::effective($action, true)],
        );
    }

    /**
     * @param  array<string, mixed>  $action
     */
    public static function ownership(array $action): string
    {
        return (string) ($action['rules']['ownership'] ?? 'none');
    }

    /**
     * @param  array<string, mixed>  $action
     */
    public static function needsConfirmation(array $action): bool
    {
        if (! empty($action['rules']['confirm'])) {
            return true;
        }

        return ($action['type'] ?? null) === 'bridge'
            && BridgeAllowlist::isWrite((string) ($action['config']['action'] ?? ''));
    }

    /**
     * @param  array<string, mixed>  $action
     */
    public static function needsOrderRef(array $action): bool
    {
        $ownership = self::ownership($action);

        if ($ownership === 'order_email_pair') {
            return true;
        }

        if ($ownership !== 'verified' || ($action['type'] ?? null) !== 'bridge') {
            return false;
        }

        return (BridgeAllowlist::find((string) ($action['config']['action'] ?? ''))['order'] ?? false) === true;
    }
}
