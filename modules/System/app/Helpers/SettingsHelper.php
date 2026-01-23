<?php

use Modules\Core\Models\Setting;

/**
 * Settings and Configuration Helpers
 *
 * Provides functions for managing application settings, logo retrieval,
 * and pagination configuration.
 */
if (! function_exists('updateSettings')) {
    /**
     * Update multiple settings at once
     *
     * @param  array  $data  Key-value pairs of settings to update
     * @return void
     */
    function updateSettings($data)
    {
        foreach ($data as $key => $val) {
            $setting = Setting::where('key', $key);
            if ($setting->exists()) {
                $setting->first()->update(['value' => $val]);
            }
        }
    }
}

if (! function_exists('setting')) {
    /**
     * Get a setting value by key
     *
     * @param  string  $key  The setting key
     * @return string|null The setting value or empty string
     */
    function setting($key)
    {
        return Setting::where('key', '=', $key)->first()->value ?? '';
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
        return $value != null ? $value : env('DEFAULT_PAGINATION');
    }
}
