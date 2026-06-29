<?php

namespace Modules\HelpdeskTranslate\Concerns;

use Modules\Helpdesk\Models\Setting;

/**
 * Shared helpers for the auto-translation listeners.
 *
 * Centralises the comparison/setting lookups that are otherwise duplicated
 * across TranslateIncomingMessage and TranslateOutgoingMessage.
 */
trait TranslatesMessage
{
    /**
     * Per-job memoization of Setting::get() results.
     *
     * Static so both listeners reuse the same lookup within the same process
     * slot. Reset between jobs by Horizon's forked worker model.
     *
     * @var array<string, mixed>
     */
    private static array $settingCache = [];

    /**
     * Compare two locale strings using their first two characters, so values
     * like "en", "EN", "en-US" or "english" still match correctly.
     */
    protected function localesMatch(string $a, string $b): bool
    {
        return strtolower(substr($a, 0, 2)) === strtolower(substr($b, 0, 2));
    }

    /**
     * Resolve a Setting value falling back to config when the stored value
     * is missing OR an explicit empty string (operators may "clear" a setting
     * by saving '' — without this helper the `??` operator skips the fallback).
     *
     * Results are memoized for the duration of the job to avoid repeated
     * SELECT queries for settings that almost never change mid-job.
     */
    protected function settingValue(string $key, mixed $default = null): mixed
    {
        if (! array_key_exists($key, self::$settingCache)) {
            self::$settingCache[$key] = Setting::get($key);
        }

        $value = self::$settingCache[$key];

        return ($value === null || $value === '') ? $default : $value;
    }

    /**
     * Locale the agent is configured to read in (admin panel setting wins,
     * module config is the fallback, then 'es' if nothing is set).
     */
    protected function agentLocale(): string
    {
        return (string) $this->settingValue(
            'helpdesktranslate.default_target',
            config('helpdesktranslate.default_target', 'es')
        );
    }
}
