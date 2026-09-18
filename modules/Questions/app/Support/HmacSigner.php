<?php

namespace Modules\Questions\Support;

/**
 * Firma compartida con el módulo alsernetreviews de PrestaShop.
 *
 * Mismo esquema que alsernetbridge: HMAC-SHA256 sobre "timestamp:cuerpo". El
 * secreto es propio de opiniones, no el del bridge, para que revocar uno no
 * arrastre al otro.
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
        if ($secret === '' || $timestamp <= 0 || $signature === '') {
            return false;
        }

        if (abs(time() - $timestamp) > $tolerance) {
            return false;
        }

        return hash_equals(self::sign($secret, $timestamp, $body), $signature);
    }
}
