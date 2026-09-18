<?php

namespace Modules\HelpdeskDocument\Support;

/**
 * Normalización de teléfonos para el match conversación↔expediente cuando el
 * cliente no tiene email (p. ej. conversaciones de WhatsApp): se comparan los
 * últimos 9 dígitos (número nacional español) ignorando prefijos y formato.
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
