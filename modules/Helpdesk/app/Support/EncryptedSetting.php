<?php

namespace Modules\Helpdesk\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Modules\Helpdesk\Models\Setting;

/**
 * Wrapper around Setting::get()/set() for credential-shaped values
 * (provider API keys, tokens, secrets) that must never sit in plaintext in
 * `helpdesk_settings.value` nor in its Redis cache (Setting::CACHE_TTL_SECONDS
 * = 300s, cached verbatim — see Setting::get()).
 *
 * SEC-09 (2026-08): audit found `helpdesktranslate.deepl.key` and
 * `helpdesktranslate.libretranslate.api_key` stored/cached in clear via plain
 * Setting::set()/get(). Same shape is very likely repeated by any other
 * module that persists a provider credential through Setting — check before
 * adding a new one:
 *
 *   grep -rn "Setting::set(" modules --include="*.php" | grep -i "key\|token\|secret\|password"
 *
 * Known sensitive keys already covered by this helper:
 *   - helpdesktranslate.deepl.key
 *   - helpdesktranslate.libretranslate.api_key
 *
 * Tolerates legacy plaintext values saved before this helper existed: if
 * Crypt::decryptString() fails, the raw value is treated as a legacy
 * plaintext credential (not an error) and returned as-is. It is re-encrypted
 * automatically the next time set() is called for that key — get() never
 * writes back by itself, so a read-only path never mutates state.
 */
class EncryptedSetting
{
    /**
     * Persist a sensitive value encrypted. Passing null/'' clears the
     * setting (stored as '' — mirrors Setting::set()'s "empty clears"
     * convention used across the settings forms) so a compromised key can be
     * revoked and callers fall back to config()/env() again.
     */
    public static function set(string $key, ?string $value, string $group = 'general'): void
    {
        $value = (string) $value;

        Setting::set($key, $value === '' ? '' : Crypt::encryptString($value), $group);
    }

    /**
     * Read a sensitive value back, decrypting it. Legacy plaintext (saved
     * before encryption was introduced) is returned as-is instead of
     * throwing, so an unmigrated row never breaks provider auth silently.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        $raw = Setting::get($key);

        if ($raw === null || $raw === '') {
            return $default;
        }

        try {
            return Crypt::decryptString($raw);
        } catch (DecryptException) {
            return $raw;
        }
    }
}
