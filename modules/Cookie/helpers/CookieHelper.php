<?php

use Modules\Core\Models\Setting;

if (! function_exists('cookie_option')) {
    function cookie_option(string $key, mixed $default = ''): mixed
    {
        static $cache = [];

        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        $value = Setting::get('cookie.'.$key);

        return $cache[$key] = ($value === null || $value === '') ? $default : $value;
    }
}
