<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

if (! function_exists('secret_env')) {
    /**
     * Read an env var that may contain either a plaintext secret or an
     * encrypted payload. Payloads prefixed with "encrypted:" are decrypted
     * with the app key; anything else is returned verbatim for backwards
     * compatibility with existing deployments.
     *
     * Usage in config files:
     *   'password' => secret_env('HELPDESK_IMAP_PASSWORD', ''),
     *
     * To encrypt a value for .env, run once:
     *   php artisan tinker --execute="echo 'encrypted:' . Crypt::encryptString('my-password');"
     * And paste the result (including the prefix) into .env.
     */
    function secret_env(string $key, ?string $default = null): ?string
    {
        $value = env($key, $default);

        if (! is_string($value) || $value === '') {
            return $value;
        }

        if (! str_starts_with($value, 'encrypted:')) {
            return $value;
        }

        try {
            return Crypt::decryptString(substr($value, 10));
        } catch (DecryptException $e) {
            // Fail closed: never surface a partially decrypted or malformed
            // secret — log and return the default so the caller sees "no
            // configured secret" instead of a broken one.
            logger()->error('secret_env: failed to decrypt value', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            return $default;
        }
    }
}
