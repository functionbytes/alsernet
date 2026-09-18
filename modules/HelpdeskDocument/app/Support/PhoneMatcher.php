<?php

namespace Modules\HelpdeskDocument\Support;

/**
 * Normalización de teléfonos para el match conversación↔expediente cuando el
 * cliente no tiene email (p. ej. conversaciones de WhatsApp): se comparan los
 * últimos 9 dígitos (número nacional español) ignorando prefijos y formato.
 *
 * Siempre trunca a los últimos 9 dígitos, con o sin prefijo internacional
 * explícito ('+'/'00'): `documents.customer_cellphone_normalized` es
 * `varchar(9)` y nunca almacena el prefijo (ni el backfill de la migración ni
 * el modelo Document al guardar), así que preservarlo aquí solo rompía el
 * match contra esa columna para el caso más común — clientes de WhatsApp,
 * cuyo teléfono casi siempre llega en formato E.164 con '+'.
 */
final class PhoneMatcher
{
    private const SIGNIFICANT_DIGITS = 9;

    public static function normalize(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if (strlen($digits) < self::SIGNIFICANT_DIGITS) {
            return null;
        }

        return substr($digits, -self::SIGNIFICANT_DIGITS);
    }

    public static function matches(?string $a, ?string $b): bool
    {
        $normalizedA = self::normalize($a);

        return $normalizedA !== null && $normalizedA === self::normalize($b);
    }
}
