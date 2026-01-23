<?php

namespace Modules\HelpdeskChat\Services\Widget;

class TokenManager
{
    /**
     * Token length in hexadecimal characters (64 = 32 bytes).
     */
    public const TOKEN_LENGTH = 64;

    /**
     * Cookie name for storing the widget token.
     */
    public const COOKIE_NAME = 'channels_chat_token';

    /**
     * Cookie lifetime in days.
     */
    public const COOKIE_LIFETIME_DAYS = 365;

    /**
     * Generate a secure random token.
     */
    public static function generate(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Validate token format.
     */
    public static function isValid(string $token): bool
    {
        return strlen($token) === self::TOKEN_LENGTH && ctype_xdigit($token);
    }

    /**
     * Get token from request (cookie or header).
     */
    public static function getFromRequest(\Illuminate\Http\Request $request): ?string
    {
        $token = $request->cookie(self::COOKIE_NAME)
            ?? $request->header('X-Chat-Token')
            ?? $request->input('token');

        if ($token && self::isValid($token)) {
            return $token;
        }

        return null;
    }

    /**
     * Create cookie with token.
     */
    public static function createCookie(string $token): \Symfony\Component\HttpFoundation\Cookie
    {
        return cookie(
            name: self::COOKIE_NAME,
            value: $token,
            minutes: self::COOKIE_LIFETIME_DAYS * 24 * 60,
            path: '/',
            domain: null,
            secure: request()->secure(),
            httpOnly: true,
            sameSite: 'lax'
        );
    }
}
