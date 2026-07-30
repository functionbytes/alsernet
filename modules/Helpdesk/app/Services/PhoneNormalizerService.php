<?php

namespace Modules\Helpdesk\Services;

/**
 * Normaliza números de teléfono para almacenamiento consistente y búsqueda en ERP.
 *
 * Formatos aceptados (entrada):
 *   +34 666 123 456  →  +34666123456
 *   0034666123456    →  +34666123456
 *   666-123-456      →  666123456
 *   (666) 123.456    →  666123456
 */
class PhoneNormalizerService
{
    /**
     * Normaliza para almacenamiento en BD.
     * Elimina espacios, guiones, paréntesis y puntos.
     * Convierte 0034 → +34 para consistencia E.164 internacional.
     */
    public function normalize(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $cleaned = preg_replace('/[\s\-\.\(\)]/', '', trim($phone));

        if (str_starts_with($cleaned, '0034')) {
            $cleaned = '+34'.substr($cleaned, 4);
        }

        return $cleaned ?: null;
    }

    /**
     * Convierte a dígitos puros para búsqueda en Oracle ERP.
     *
     * El manager Oracle usa heurística ctype_digit() para detectar
     * búsquedas por teléfono — necesita recibir solo dígitos (≥6).
     * Para España: elimina el prefijo +34 / 0034 / 34 (E.164 sin '+', formato
     * que entrega WhatsApp Cloud API) y devuelve los 9 dígitos.
     */
    public function toDigits(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $cleaned = preg_replace('/[\s\-\.\(\)]/', '', trim($phone));

        if (str_starts_with($cleaned, '+34')) {
            $cleaned = substr($cleaned, 3);
        } elseif (str_starts_with($cleaned, '0034')) {
            $cleaned = substr($cleaned, 4);
        }

        $digits = preg_replace('/\D/', '', $cleaned);

        // WhatsApp Cloud API entrega E.164 sin '+' (ej. '34615490503').
        // Strip '34' sólo cuando el patrón es inequívocamente español:
        // 11 dígitos totales y el primero tras '34' es móvil/fijo ES (6/7/8/9).
        if (strlen($digits) === 11 && str_starts_with($digits, '34') && preg_match('/^[6789]/', substr($digits, 2))) {
            $digits = substr($digits, 2);
        }

        return strlen($digits) >= 6 ? $digits : null;
    }

    /**
     * Devuelve true si dos teléfonos apuntan al mismo número tras normalización.
     */
    public function similar(?string $a, ?string $b): bool
    {
        $da = $this->toDigits($a);
        $db = $this->toDigits($b);

        return $da !== null && $db !== null && $da === $db;
    }
}
