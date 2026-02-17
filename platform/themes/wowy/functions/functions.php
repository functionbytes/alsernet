<?php

/**
 * Funciones Generales - Plantilla Wowy
 *
 * Nota: Wowy fue diseñado para Botble.
 * Aquí incluimos solo las funciones compatibles con inoqualab
 */

// Dummy helper functions para compatibilidad
if (! function_exists('register_page_template')) {
    function register_page_template($templates)
    {
        return true;
    }
}

if (! function_exists('register_sidebar')) {
    function register_sidebar($args)
    {
        return true;
    }
}

if (! function_exists('get_application_currency')) {
    function get_application_currency()
    {
        return (object) [
            'symbol' => '$',
            'title' => 'USD',
            'decimals' => 2,
            'is_prefix_symbol' => true,
        ];
    }
}

if (! function_exists('get_ecommerce_setting')) {
    function get_ecommerce_setting($key, $default = null)
    {
        return $default;
    }
}

if (! function_exists('theme_url')) {
    function theme_url(string $path = ''): string
    {
        return config('app.url').'/platform/themes/wowy/'.ltrim($path, '/');
    }
}

if (! function_exists('theme_asset')) {
    function theme_asset(string $path): string
    {
        return theme_url('public/'.ltrim($path, '/'));
    }
}

if (! function_exists('theme_image')) {
    function theme_image(string $path): string
    {
        return theme_asset('images/'.ltrim($path, '/'));
    }
}

if (! function_exists('is_rtl')) {
    function is_rtl(): bool
    {
        return in_array(app()->getLocale(), config('template.rtl_languages', ['ar', 'he']));
    }
}

if (! function_exists('rtl_class')) {
    function rtl_class(): string
    {
        return is_rtl() ? 'rtl' : 'ltr';
    }
}

if (! function_exists('get_text_direction')) {
    function get_text_direction(): string
    {
        return is_rtl() ? 'rtl' : 'ltr';
    }
}

if (! function_exists('theme_trans')) {
    function theme_trans(string $key, array $replace = []): string
    {
        return __($key);
    }
}
