<?php

/**
 * Funciones Generales - Plantilla Default
 *
 * Este archivo contiene funciones reutilizables específicas de la plantilla Default
 */

/**
 * Obtener URL de tema
 */
if (!function_exists('theme_url')) {
    function theme_url(string $path = ''): string
    {
        return config('app.url') . '/platform/themes/default/' . ltrim($path, '/');
    }
}

/**
 * Obtener URL de asset de tema
 */
if (!function_exists('theme_asset')) {
    function theme_asset(string $path): string
    {
        return theme_url('public/' . ltrim($path, '/'));
    }
}

/**
 * Obtener URL de imagen de tema
 */
if (!function_exists('theme_image')) {
    function theme_image(string $path): string
    {
        return theme_asset('images/' . ltrim($path, '/'));
    }
}

/**
 * Obtener configuración de tema
 */
if (!function_exists('theme_config')) {
    function theme_config(string $key, mixed $default = null): mixed
    {
        $config = include __DIR__ . '/../config.php';
        return data_get($config, $key, $default);
    }
}

/**
 * Traducción de tema
 */
if (!function_exists('theme_trans')) {
    function theme_trans(string $key, array $replace = []): string
    {
        // Try to get from JSON translation files (platform/themes/{template}/lang/)
        $translation = __($key);

        // If translation contains the key itself (meaning it wasn't found), return a fallback
        if ($translation === $key && !empty($replace)) {
            foreach ($replace as $search => $replace_value) {
                $translation = str_replace(':' . $search, $replace_value, $translation);
            }
        }

        return $translation;
    }
}

/**
 * Obtener layout activo
 */
if (!function_exists('get_active_layout')) {
    function get_active_layout(): string
    {
        return request()->get('layout', 'default');
    }
}

/**
 * Verificar si layout es activo
 */
if (!function_exists('is_layout_active')) {
    function is_layout_active(string $layout): bool
    {
        return get_active_layout() === $layout;
    }
}

/**
 * Verificar si sidebar está activo
 */
if (!function_exists('has_sidebar')) {
    function has_sidebar(): bool
    {
        $layout = get_active_layout();
        return !str_contains($layout, 'full-width');
    }
}

/**
 * Obtener posición del sidebar
 */
if (!function_exists('get_sidebar_position')) {
    function get_sidebar_position(): string
    {
        return theme_config('options.sidebar_position', 'right');
    }
}

/**
 * Verificar RTL
 */
if (!function_exists('is_rtl')) {
    function is_rtl(): bool
    {
        return in_array(app()->getLocale(), config('template.rtl_languages', ['ar', 'he']));
    }
}

/**
 * Clase RTL
 */
if (!function_exists('rtl_class')) {
    function rtl_class(): string
    {
        return is_rtl() ? 'rtl' : 'ltr';
    }
}

/**
 * Obtener dirección de texto
 */
if (!function_exists('get_text_direction')) {
    function get_text_direction(): string
    {
        return is_rtl() ? 'rtl' : 'ltr';
    }
}

/**
 * Traducción JSON de tema
 */
if (!function_exists('theme_trans_json')) {
    function theme_trans_json(string $key, string $default = ''): string
    {
        return trans('template::default.' . $key, [], $default);
    }
}

/**
 * Verificar si es mobile
 */
if (!function_exists('is_mobile')) {
    function is_mobile(): bool
    {
        return request()->header('User-Agent', '') && preg_match('/mobile|android|iphone|ipod|blackberry/i', request()->header('User-Agent'));
    }
}
