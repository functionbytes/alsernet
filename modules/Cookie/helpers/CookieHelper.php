<?php

if (! function_exists('cookie_option')) {
    /**
     * Get a cookie configuration value from database.
     * Falls back to default if empty.
     */
    function cookie_option(string $key, mixed $default = ''): mixed
    {
        $value = \Modules\Core\Models\Setting::get('cookie.'.$key);

        // Return default if value is null or empty string
        if ($value === null || $value === '') {
            return $default;
        }

        return $value;
    }
}
