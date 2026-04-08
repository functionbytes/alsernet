<?php

use Illuminate\Support\Facades\Cache;
use Modules\Core\Models\Setting;

/**
 * Settings and Configuration Helpers
 *
 * Provides functions for managing application settings, logo retrieval,
 * and pagination configuration.
 */
if (! function_exists('updateSettings')) {
    /**
     * Update multiple settings at once using a single upsert query.
     *
     * @param  array<string, mixed>  $data  Key-value pairs of settings to update
     */
    function updateSettings(array $data): void
    {
        if (empty($data)) {
            return;
        }

        $rows = collect($data)
            ->map(fn ($val, $key) => [
                'key' => $key,
                'value' => is_array($val) ? json_encode($val) : $val,
            ])
            ->values()
            ->all();

        Setting::upsert($rows, ['key'], ['value']);

        Cache::forget('settings');
    }
}

if (! function_exists('setting')) {
    /**
     * Get a setting value by key
     *
     * @param  string  $key  The setting key
     * @param  mixed  $default  Value to return if setting is not found
     * @return mixed The setting value or default
     */
    function setting($key, $default = '')
    {
        return Setting::where('key', '=', $key)->first()->value ?? $default;
    }
}

if (! function_exists('getLogo')) {
    /**
     * Get the application logo URL
     *
     * @return string The URL of the logo image
     */
    function getLogo()
    {
        $setting = Setting::where('key', '=', 'page_logo')->first();

        return count($setting->getMedia('logo')) > 0 ? $setting->getfirstMedia('logo')->getfullUrl() : asset('/pages/images/logo.png');
    }
}

if (! function_exists('paginationNumber')) {
    /**
     * Get the default pagination number of items per page
     *
     * @param  int|null  $value  Override value, if provided
     * @return int The number of items per page
     */
    function paginationNumber($value = null)
    {
        return $value != null ? $value : config('app.default_pagination', 20);
    }
}
