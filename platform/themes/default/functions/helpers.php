<?php

/**
 * Helpers - Plantilla Default
 *
 * Funciones helper comúnmente usadas en vistas
 */

/**
 * Clase CSS para dirección RTL/LTR
 */
if (!function_exists('theme_direction_class')) {
    function theme_direction_class(string $ltr_class = '', string $rtl_class = ''): string
    {
        return is_rtl() ? $rtl_class : $ltr_class;
    }
}

/**
 * Margin/Padding con soporte RTL
 */
if (!function_exists('theme_spacing_class')) {
    function theme_spacing_class(string $direction, int $value = 1): string
    {
        $direction = strtolower($direction);

        if (is_rtl() && in_array($direction, ['left', 'right'])) {
            $direction = $direction === 'left' ? 'right' : 'left';
        }

        return "{$direction}-{$value}";
    }
}

/**
 * Clase para alineación con soporte RTL
 */
if (!function_exists('theme_text_align')) {
    function theme_text_align(string $align = 'left'): string
    {
        if (is_rtl() && in_array($align, ['left', 'right'])) {
            $align = $align === 'left' ? 'right' : 'left';
        }

        return "text-{$align}";
    }
}

/**
 * Truncar texto
 */
if (!function_exists('theme_truncate')) {
    function theme_truncate(string $text, int $length = 100, string $suffix = '...'): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length) . $suffix;
    }
}

/**
 * Obtener color aleatorio
 */
if (!function_exists('theme_random_color')) {
    function theme_random_color(): string
    {
        $colors = ['primary', 'secondary', 'success', 'danger', 'warning', 'info'];
        return $colors[array_rand($colors)];
    }
}

/**
 * Formatear precio
 */
if (!function_exists('theme_format_price')) {
    function theme_format_price(float $price, string $currency = null): string
    {
        $currency = $currency ?? config('app.currency', 'USD');

        return number_format($price, 2) . ' ' . $currency;
    }
}

/**
 * Obtener avatar por defecto
 */
if (!function_exists('theme_default_avatar')) {
    function theme_default_avatar(string $name = 'User'): string
    {
        $initials = strtoupper(substr($name, 0, 1));

        return "https://via.placeholder.com/40?text={$initials}";
    }
}

/**
 * Truncar nombre largo
 */
if (!function_exists('theme_truncate_name')) {
    function theme_truncate_name(string $name, int $max = 15): string
    {
        return mb_strlen($name) > $max ? mb_substr($name, 0, $max) . '...' : $name;
    }
}

/**
 * Badge de estado
 */
if (!function_exists('theme_status_badge')) {
    function theme_status_badge(string $status): string
    {
        $badges = [
            'active' => 'success',
            'inactive' => 'secondary',
            'pending' => 'warning',
            'archived' => 'danger',
        ];

        $badge_class = $badges[$status] ?? 'secondary';

        return "<span class=\"badge bg-{$badge_class}\">$status</span>";
    }
}

/**
 * Ícono según tipo
 */
if (!function_exists('theme_icon')) {
    function theme_icon(string $icon, string $class = ''): string
    {
        return "<i class=\"fas fa-{$icon} {$class}\"></i>";
    }
}

/**
 * Generar atributos HTML
 */
if (!function_exists('theme_attr')) {
    function theme_attr(array $attributes = []): string
    {
        return collect($attributes)
            ->map(fn ($value, $key) => "{$key}=\"{$value}\"")
            ->implode(' ');
    }
}
