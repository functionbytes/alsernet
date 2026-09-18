<?php

namespace Modules\HelpdeskPrestashop\Support;

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
