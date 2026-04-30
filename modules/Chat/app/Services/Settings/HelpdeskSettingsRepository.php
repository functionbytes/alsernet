<?php

namespace Modules\Chat\Services\Settings;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Persists chat settings to the `settings` table and keeps them in cache.
 *
 * Cache is the primary read path; the DB row acts as a durable fallback
 * so that settings survive a full cache flush without data loss.
 */
class HelpdeskSettingsRepository
{
    /**
     * Load settings for the given key, merging stored values over $defaults.
     *
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    public function get(string $key, array $defaults = []): array
    {
        $stored = Cache::get($key);

        if ($stored === null) {
            $raw = DB::table('settings')->where('key', $key)->value('value');
            $stored = $raw ? json_decode($raw, true) : [];
        }

        return array_merge($defaults, $stored);
    }

    /**
     * Persist settings to both cache (365 days) and the `settings` table.
     *
     * @param  array<string, mixed>  $values
     */
    public function save(string $key, array $values): void
    {
        Cache::put($key, $values, now()->addDays(365));

        DB::table('settings')->updateOrInsert(
            ['key' => $key],
            ['value' => json_encode($values), 'updated_at' => now()]
        );
    }
}
