<?php

namespace Modules\HelpdeskErp\Services\ErpChat;

/**
 * Lista blanca de secciones del manager que la interfaz puede pedir, con su
 * ruta en /api/erp/customer/{id}/..., los permisos (basta con UNO de la
 * lista, además de helpdeskerp.view) y los parámetros que se reenvían.
 */
final class ErpChatSections
{
    public const PERM_VIEW = 'helpdeskerp.view';

    public const PERM_ORDERS = 'helpdeskerp.orders.view';

    public const PERM_ADDRESSES = 'helpdeskerp.addresses.view';

    public const PERM_FINANCE = 'helpdeskerp.finance.view';

    public const PERM_LOYALTY = 'helpdeskerp.loyalty.view';

    public const PERM_SENSITIVE = 'helpdeskerp.sensitive.view';

    /**
     * @var array<string, array{path: string, perms: list<string>, params: list<string>}>
     */
    public const ALL = [
        'summary' => ['path' => '', 'perms' => [], 'params' => []],
        'personal' => ['path' => 'personal', 'perms' => [], 'params' => []],
        'contact' => ['path' => 'contact', 'perms' => [], 'params' => []],
        'catalogs' => ['path' => 'catalogs', 'perms' => [], 'params' => []],
        'addresses' => ['path' => 'addresses', 'perms' => [self::PERM_ADDRESSES], 'params' => []],
        'quotas' => ['path' => 'quotas', 'perms' => [self::PERM_FINANCE], 'params' => []],
        'cards' => ['path' => 'cards', 'perms' => [self::PERM_SENSITIVE], 'params' => []],
        'accounts' => ['path' => 'accounts', 'perms' => [self::PERM_SENSITIVE], 'params' => []],
        'orders' => ['path' => 'orders', 'perms' => [self::PERM_ORDERS], 'params' => ['limit', 'offset', 'status', 'from', 'to']],
        'delivery-notes' => ['path' => 'delivery-notes', 'perms' => [self::PERM_ORDERS, self::PERM_FINANCE], 'params' => ['limit', 'offset', 'status', 'from', 'to']],
        'returns' => ['path' => 'returns', 'perms' => [self::PERM_ORDERS, self::PERM_FINANCE], 'params' => ['limit', 'offset']],
        'invoices' => ['path' => 'invoices', 'perms' => [self::PERM_FINANCE], 'params' => ['limit', 'offset', 'status', 'year', 'from', 'to']],
        'payments' => ['path' => 'payments', 'perms' => [self::PERM_FINANCE], 'params' => ['limit', 'offset', 'from', 'to']],
        'debts' => ['path' => 'debts', 'perms' => [self::PERM_FINANCE], 'params' => []],
        'balance' => ['path' => 'balance', 'perms' => [self::PERM_FINANCE], 'params' => []],
        'vouchers' => ['path' => 'vouchers', 'perms' => [self::PERM_LOYALTY], 'params' => []],
        'bonuses' => ['path' => 'bonuses', 'perms' => [self::PERM_LOYALTY], 'params' => []],
        'loyalty-points' => ['path' => 'loyalty-points', 'perms' => [self::PERM_LOYALTY], 'params' => []],
    ];

    /** Permisos de los detalles (cualquiera de la lista). */
    public const DETAIL_PERMS = [
        'order' => [self::PERM_ORDERS],
        'delivery-note' => [self::PERM_ORDERS, self::PERM_FINANCE],
        'invoice' => [self::PERM_FINANCE],
    ];

    /** Permisos nuevos de la gestión en el chat, con su descripción. */
    public const PERMISSIONS = [
        self::PERM_ORDERS => 'Ver pedidos, albaranes y devoluciones del ERP en el chat',
        self::PERM_ADDRESSES => 'Ver direcciones del cliente en el ERP',
        self::PERM_FINANCE => 'Ver finanzas del cliente en el ERP (facturas, cobros, deudas, saldo)',
        self::PERM_LOYALTY => 'Ver fidelización del cliente en el ERP (puntos, vales, bonos)',
        self::PERM_SENSITIVE => 'Ver tarjetas y cuentas bancarias del cliente en el ERP',
    ];

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_keys(self::ALL);
    }

    public static function exists(string $section): bool
    {
        return isset(self::ALL[$section]);
    }

    /**
     * @return list<string>
     */
    public static function permsFor(string $section): array
    {
        return self::ALL[$section]['perms'] ?? [];
    }

    /**
     * Filtra los parámetros a los que la sección admite.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, scalar>
     */
    public static function paramsFor(string $section, array $params): array
    {
        $allowed = self::ALL[$section]['params'] ?? [];
        $out = [];

        foreach ($allowed as $key) {
            if (isset($params[$key]) && $params[$key] !== '' && is_scalar($params[$key])) {
                $out[$key] = $params[$key];
            }
        }

        ksort($out);

        return $out;
    }

    /**
     * Enmascara datos de pago ANTES de cachear o devolver nada: el manager
     * entrega el número de tarjeta completo (numerotarjeta) tanto en /cards
     * como en el resumen, y el IBAN ya enmascarado (se re-enmascara por si
     * acaso). Solo quedan visibles los 4 últimos dígitos.
     */
    public static function maskSensitive(string $section, mixed $data): mixed
    {
        if (! is_array($data)) {
            return $data;
        }

        if (in_array($section, ['summary', 'cards'], true) && is_array($data['cards'] ?? null)) {
            foreach ($data['cards'] as $i => $card) {
                if (is_array($card) && array_key_exists('number', $card)) {
                    $data['cards'][$i]['number'] = self::maskTail($card['number']);
                }
            }
        }

        if ($section === 'accounts' && is_array($data['accounts'] ?? null)) {
            foreach ($data['accounts'] as $i => $account) {
                if (! is_array($account)) {
                    continue;
                }
                if (array_key_exists('iban', $account)) {
                    $data['accounts'][$i]['iban'] = self::maskIban($account['iban']);
                }
                if (is_array($account['legacy'] ?? null) && array_key_exists('number', $account['legacy'])) {
                    $data['accounts'][$i]['legacy']['number'] = self::maskTail($account['legacy']['number']);
                }
            }
        }

        return $data;
    }

    /**
     * Recorta del resultado lo que el agente no puede ver aunque venga dentro
     * de otra sección: el resumen del manager trae tarjetas (sensitive) y
     * direcciones (addresses). Se aplica por usuario DESPUÉS de la caché,
     * que es compartida entre agentes.
     *
     * @param  array<string, mixed>  $result  resultado normalizado {state, data, ...}
     * @param  callable(string): bool  $can
     * @return array<string, mixed>
     */
    public static function redactFor(string $section, array $result, callable $can): array
    {
        if (! is_array($result['data'] ?? null)) {
            return $result;
        }

        // Idempotente: cubre también entradas de caché anteriores al enmascarado.
        $result['data'] = self::maskSensitive($section, $result['data']);

        if ($section !== 'summary') {
            return $result;
        }

        if (! $can(self::PERM_SENSITIVE)) {
            $result['data']['cards'] = [];
            if (is_array($result['data']['statistics']['cards'] ?? null)) {
                $result['data']['statistics']['cards']['total'] = null;
            }
        }

        if (! $can(self::PERM_ADDRESSES)) {
            $result['data']['addresses'] = [];
            if (is_array($result['data']['statistics']['addresses'] ?? null)) {
                $result['data']['statistics']['addresses']['total'] = null;
            }
        }

        return $result;
    }

    public static function maskTail(mixed $value): mixed
    {
        if (! is_scalar($value) || $value === '') {
            return $value;
        }

        $clean = preg_replace('/[\s-]+/', '', (string) $value) ?? '';

        if (strlen($clean) <= 4) {
            return str_repeat('*', strlen($clean));
        }

        return str_repeat('*', strlen($clean) - 4).substr($clean, -4);
    }

    public static function maskIban(mixed $value): mixed
    {
        if (! is_scalar($value) || $value === '') {
            return $value;
        }

        $clean = preg_replace('/\s+/', '', (string) $value) ?? '';

        if (strlen($clean) <= 8) {
            return self::maskTail($clean);
        }

        // País + dígito de control, asteriscos, 4 últimos.
        return substr($clean, 0, 4).str_repeat('*', strlen($clean) - 8).substr($clean, -4);
    }
}
