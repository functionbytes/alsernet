<?php

if (! function_exists('shortcode')) {
    /**
     * Compile shortcodes in content, or return the compiler instance when called with no args.
     */
    function shortcode(?string $content = null): mixed
    {
        $compiler = app('shortcode');

        if ($content === null) {
            return $compiler;
        }

        return $compiler->compile($content);
    }
}

if (! function_exists('do_shortcode')) {
    /**
     * Alias estilo WordPress para shortcode().
     */
    function do_shortcode(string $content): string
    {
        return app('shortcode')->compile($content);
    }
}

if (! function_exists('strip_shortcodes')) {
    /**
     * Strip all shortcodes from content.
     */
    function strip_shortcodes(string $content): string
    {
        return app('shortcode')->strip($content);
    }
}

if (! function_exists('register_shortcode')) {
    /**
     * Register a new shortcode.
     *
     * @param  array{description?: string, example?: string, attributes?: array<string, string>}  $meta
     */
    function register_shortcode(string $name, callable $callback, array $meta = []): void
    {
        app('shortcode')->register($name, $callback, $meta);
    }
}

if (! function_exists('has_shortcode')) {
    /**
     * Check if a shortcode exists.
     */
    function has_shortcode(string $name): bool
    {
        return app('shortcode')->has($name);
    }
}

if (! function_exists('all_shortcodes')) {
    /**
     * Get all registered shortcode names.
     *
     * @return array<int, string>
     */
    function all_shortcodes(): array
    {
        return app('shortcode')->all();
    }
}

if (! function_exists('all_shortcodes_registered')) {
    /**
     * Get registered shortcodes with full metadata.
     *
     * @return array<int, array{name: string, description: string, example: string, attributes: array<string, string>}>
     */
    function all_shortcodes_registered(): array
    {
        return app('shortcode')->getRegistered();
    }
}

if (! function_exists('shortcode_atts')) {
    /**
     * Merge shortcode attributes with defaults, dropping unknown keys.
     *
     * Helper estilo WordPress para usarse dentro del callback de un shortcode:
     *
     *     $attrs = shortcode_atts(['class' => 'primary', 'url' => '#'], $attrs);
     *
     * @param  array<string, mixed>  $defaults
     * @param  array<string, mixed>  $atts
     * @return array<string, mixed>
     */
    function shortcode_atts(array $defaults, array $atts): array
    {
        return app('shortcode')->atts($defaults, $atts);
    }
}
