<?php

namespace Modules\Forms\Support;

/**
 * Mismo esquema que Modules\HelpdeskPrestashop\Support\HmacSigner (y que
 * ApiManager::buildSignedHeaders() del lado alsernetforms/PrestaShop):
 * hash_hmac('sha256', "{timestamp}:{body}", secret). Copia deliberada en vez
 * de dependencia cruzada a HelpdeskPrestashop -- es una utilidad genérica sin
 * nada específico de PrestaShop, y cada módulo satélite mantiene sus propias
 * dependencias mínimas (ver module.json: requires solo Helpdesk/HelpdeskTickets).
 */
class HmacSigner
{
    public const TIMESTAMP_TOLERANCE_SECONDS = 300;

    public static function sign(string $secret, int $timestamp, string $body): string
    {
        return hash_hmac('sha256', $timestamp.':'.$body, $secret);
    }

    public static function verify(
        string $secret,
        int $timestamp,
        string $body,
        string $signature,
        int $tolerance = self::TIMESTAMP_TOLERANCE_SECONDS
    ): bool {
        if ($timestamp <= 0 || abs(time() - $timestamp) > $tolerance) {
            return false;
        }

        if ($signature === '') {
            return false;
        }

        return hash_equals(self::sign($secret, $timestamp, $body), $signature);
    }
}
