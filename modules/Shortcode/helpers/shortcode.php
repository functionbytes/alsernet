<?php

if (! function_exists('shortcode')) {
    /**
     * Compile shortcodes in content
     */
    function shortcode(string $content): string
    {
        return app('shortcode')->compile($content);
    }
}

if (! function_exists('strip_shortcodes')) {
    /**
     * Strip all shortcodes from content
     */
    function strip_shortcodes(string $content): string
    {
        return app('shortcode')->strip($content);
    }
}

if (! function_exists('register_shortcode')) {
    /**
     * Register a new shortcode
     */
    function register_shortcode(string $name, callable $callback): void
    {
        app('shortcode')->register($name, $callback);
    }
}

if (! function_exists('has_shortcode')) {
    /**
     * Check if a shortcode exists
     */
    function has_shortcode(string $name): bool
    {
        return app('shortcode')->has($name);
    }
}

if (! function_exists('all_shortcodes')) {
    /**
     * Get all registered shortcodes
     */
    function all_shortcodes(): array
    {
        return app('shortcode')->all();
    }
}
