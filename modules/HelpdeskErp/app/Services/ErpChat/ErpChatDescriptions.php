<?php

namespace Modules\HelpdeskErp\Services\ErpChat;

/**
 * Descripciones de los códigos del ERP en las secciones normalizadas.
 *
 * El manager ya resuelve los códigos contra las tablas de Oracle (ALMACEN,
 * ORIGENPEDIDOCLI, CATALOGO, PEDIDOCLIESTADO, IDIOMA, PAIS, REGFISCAL…) y
 * entrega campos *_description al lado del código. Aquí solo se garantiza que
 * esas claves EXISTEN siempre (null si no hay descripción) y, cuando el manager
 * no la trae (versión anterior, tabla sin GRANT), se completa con el mapa de
 * respaldo de config('helpdeskErp.chat_codes'). El código original nunca se
 * toca: la interfaz lo usa como respaldo ("Almacén 6").
 */
final class ErpChatDescriptions
{
    /**
     * Campos por sección: código => [campo de descripción, mapa de respaldo|null].
     *
     * @var array<string, array<string, array{0: string, 1: string|null}>>
     */
    private const LIST_FIELDS = [
        'orders' => [
            'status' => ['status_description', 'order_status'],
            'warehouse' => ['warehouse_description', 'warehouse'],
            'origin' => ['origin_description', 'origin'],
            'catalog' => ['catalog_description', 'catalog'],
        ],
        'delivery-notes' => [
            'warehouse' => ['warehouse_description', 'warehouse'],
            'catalog' => ['catalog_description', 'catalog'],
            'type' => ['type_description', 'delivery_type'],
        ],
        'invoices' => [
            'warehouse' => ['warehouse_description', 'warehouse'],
            'catalog' => ['catalog_description', 'catalog'],
        ],
    ];

    /** Fichas (un objeto, no una lista). */
    private const RECORD_FIELDS = [
        'summary' => [
            'language' => ['language_description', null],
            'category' => ['category_description', null],
        ],
        'personal' => [
            'language' => ['language_description', null],
            'category' => ['category_description', null],
            'customer_type' => ['customer_type_description', null],
            'fiscal_regime' => ['fiscal_regime_description', null],
            'country_regime' => ['country_regime_description', null],
            'nationality' => ['nationality_description', null],
        ],
        'delivery-note' => [
            'warehouse' => ['warehouse_description', 'warehouse'],
            'catalog' => ['catalog_description', 'catalog'],
            'type' => ['type_description', 'delivery_type'],
        ],
    ];

    /** Listas anidadas dentro de una ficha: sección => [clave de la lista, campos]. */
    private const NESTED_FIELDS = [
        'catalogs' => ['catalogs', ['catalog_id' => ['catalog_description', 'catalog']]],
        'loyalty-points' => ['movements', ['warehouse' => ['warehouse_description', 'warehouse']]],
        'vouchers' => ['vouchers', ['warehouse' => ['warehouse_description', 'warehouse']]],
    ];

    /**
     * Completa las descripciones de un resultado normalizado {state, data, …}.
     * Solo actúa sobre state 'ok'; es idempotente.
     *
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    public static function apply(string $section, array $result): array
    {
        if (($result['state'] ?? null) !== 'ok' || ! is_array($result['data'] ?? null)) {
            return $result;
        }

        $result['data'] = self::decorate($section, $result['data']);

        return $result;
    }

    /**
     * @param  array<mixed>  $data
     * @return array<mixed>
     */
    public static function decorate(string $section, array $data): array
    {
        if (isset(self::LIST_FIELDS[$section]) && array_is_list($data)) {
            foreach ($data as $i => $item) {
                if (is_array($item)) {
                    $data[$i] = self::fill($item, self::LIST_FIELDS[$section]);
                }
            }

            return $data;
        }

        if (isset(self::RECORD_FIELDS[$section])) {
            return self::fill($data, self::RECORD_FIELDS[$section]);
        }

        if (isset(self::NESTED_FIELDS[$section])) {
            [$listKey, $fields] = self::NESTED_FIELDS[$section];
            if (is_array($data[$listKey] ?? null)) {
                foreach ($data[$listKey] as $i => $item) {
                    if (is_array($item)) {
                        $data[$listKey][$i] = self::fill($item, $fields);
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Descripción de respaldo de un código (config helpdeskErp.chat_codes.{map}).
     */
    public static function fallback(string $map, mixed $code): ?string
    {
        if (! is_scalar($code) || is_bool($code)) {
            return null;
        }

        $code = trim((string) $code);
        if ($code === '') {
            return null;
        }

        $value = config('helpdeskErp.chat_codes.'.$map.'.'.$code);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<string, array{0: string, 1: string|null}>  $fields
     * @return array<string, mixed>
     */
    private static function fill(array $item, array $fields): array
    {
        foreach ($fields as $codeKey => [$descKey, $map]) {
            $current = $item[$descKey] ?? null;

            if (is_string($current) && trim($current) !== '') {
                continue;
            }

            $item[$descKey] = $map !== null ? self::fallback($map, $item[$codeKey] ?? null) : null;
        }

        return $item;
    }
}
