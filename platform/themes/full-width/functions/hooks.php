<?php

/**
 * Hooks & Events - Plantilla Default
 *
 * Hooks y eventos específicos de la plantilla Default
 */

/**
 * Hook: theme_before_render
 * Se dispara antes de renderizar la plantilla
 */
if (!function_exists('do_action_before_render')) {
    function do_action_before_render(): void
    {
        // Agregar assets globales
        // Cargar configuración
        // Preparar datos
    }
}

/**
 * Hook: theme_after_render
 * Se dispara después de renderizar la plantilla
 */
if (!function_exists('do_action_after_render')) {
    function do_action_after_render(): void
    {
        // Limpiar caché
        // Agregar analytics
    }
}

/**
 * Hook: theme_header_before
 * Antes de renderizar header
 */
if (!function_exists('do_action_header_before')) {
    function do_action_header_before(): void
    {
        // Acciones antes del header
    }
}

/**
 * Hook: theme_footer_before
 * Antes de renderizar footer
 */
if (!function_exists('do_action_footer_before')) {
    function do_action_footer_before(): void
    {
        // Acciones antes del footer
    }
}

/**
 * Hook: theme_sidebar_before
 * Antes de renderizar sidebar
 */
if (!function_exists('do_action_sidebar_before')) {
    function do_action_sidebar_before(): void
    {
        // Acciones antes del sidebar
    }
}

/**
 * Hook: theme_content_before
 * Antes del contenido principal
 */
if (!function_exists('do_action_content_before')) {
    function do_action_content_before(): void
    {
        // Acciones antes del contenido
    }
}

/**
 * Hook: theme_content_after
 * Después del contenido principal
 */
if (!function_exists('do_action_content_after')) {
    function do_action_content_after(): void
    {
        // Acciones después del contenido
    }
}

/**
 * Registrar hook
 */
if (!function_exists('add_theme_hook')) {
    function add_theme_hook(string $hook, callable $callback): void
    {
        app('template.hooks')->add($hook, $callback);
    }
}

/**
 * Disparar hook
 */
if (!function_exists('do_theme_hook')) {
    function do_theme_hook(string $hook, ...$args): mixed
    {
        return app('template.hooks')->execute($hook, ...$args);
    }
}

/**
 * Obtener valor filtrado
 */
if (!function_exists('apply_theme_filter')) {
    function apply_theme_filter(string $filter, mixed $value, ...$args): mixed
    {
        return app('template.filters')->apply($filter, $value, ...$args);
    }
}

/**
 * Registrar filtro
 */
if (!function_exists('add_theme_filter')) {
    function add_theme_filter(string $filter, callable $callback): void
    {
        app('template.filters')->add($filter, $callback);
    }
}
