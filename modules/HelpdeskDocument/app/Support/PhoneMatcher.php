<?php

namespace Modules\HelpdeskDocument\Support;

/**
 * Normalización de teléfonos para el match conversación↔expediente cuando el
 * cliente no tiene email (p. ej. conversaciones de WhatsApp): se comparan los
 * últimos 9 dígitos (número nacional español) ignorando prefijos y formato.
 *
 * Cuando el número trae un prefijo internacional explícito ('+' o '00') se
 * conserva completo en vez de truncarlo: truncar a los últimos 9 dígitos
 * hacía colisionar números de países distintos que comparten esos 9 dígitos
 * finales (p. ej. +34600123456 y +351600123456 se consideraban el mismo
 * dueño). Los números SIN prefijo explícito (la mayoría de los datos
 * existentes, asumidos España) mantienen el comportamiento previo para no
 * romper la columna indexada `documents.customer_cellphone_normalized`
 * (ConversationDocumentLinker, DocumentGdprExportContributor,
 * DocumentComplianceHandler), que solo se rellenó nunca con esa forma
 * truncada de 9 dígitos.
 */
final class PhoneMatcher
{
    private const SIGNIFICANT_DIGITS = 9;

    /**
     * Límite superior de dígitos de un número E.164 válido (sin el '+').
     */
    private const MAX_E164_DIGITS = 15;

    public static function normalize(?string $phone): ?string
    {
        $raw = (string) $phone;
        $hasExplicitCountryCode = (bool) preg_match('/^\s*(\+|00)/', $raw);

        $digits = preg_replace('/\D+/', '', $raw);

        if ($hasExplicitCountryCode) {
            // "00" es el prefijo de marcación internacional (equivalente a '+')
            // en digits ya incluye esos dos ceros: quitarlos para no contarlos
            // como parte del número.
            if (str_starts_with(ltrim($raw), '00')) {
                $digits = substr($digits, 2);
            }

            if (strlen($digits) < self::SIGNIFICANT_DIGITS || strlen($digits) > self::MAX_E164_DIGITS) {
                return null;
            }

            return '+'.$digits;
        }

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
