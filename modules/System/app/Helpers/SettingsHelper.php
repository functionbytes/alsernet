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
     * Guarda varios ajustes a la vez.
     *
     * 29-sep-2026: pasa por Setting::set() para que los secretos se cifren y se
     * invalide la misma caché ("setting_{key}") que lee setting(); el upsert
     * directo dejaba la caché vieja hasta diez minutos.
     *
     * @param  array<string, mixed>  $data  Key-value pairs of settings to update
     */
    function updateSettings(array $data): void
    {
        if (empty($data)) {
            return;
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            foreach ($data as $key => $val) {
                Setting::set($key, is_array($val) ? json_encode($val) : $val);
            }
        });

        Cache::forget('settings');
    }
}

if (! function_exists('setting')) {
    /**
     * Valor de un ajuste (tabla settings), o $default si no existe.
     *
     * Delegado en Setting::get() (caché por clave, valores por defecto del
     * modelo y descifrado de secretos).
     *
     * @param  string  $key  The setting key
     * @param  mixed  $default  Value to return if setting is not found
     * @return mixed The setting value or default
     */
    function setting($key, $default = null)
    {
        return Setting::get($key, $default);
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
        return Cache::remember('setting.logo_url', 300, function () {
            $setting = Setting::where('key', '=', 'page_logo')->first();

            if (! $setting) {
                return asset('/pages/images/logo.png');
            }

            $media = $setting->getMedia('logo');

            return count($media) > 0 ? $media->first()->getFullUrl() : asset('/pages/images/logo.png');
        });
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
