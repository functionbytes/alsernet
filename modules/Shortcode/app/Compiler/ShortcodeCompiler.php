<?php

namespace Modules\Shortcode\Compiler;

class ShortcodeCompiler
{
    /**
     * Registered shortcodes
     */
    protected array $shortcodes = [];

    /**
     * Cache for compiled shortcodes
     */
    protected array $cache = [];

    /**
     * Register a new shortcode
     */
    public function register(string $name, callable $callback): void
    {
        $this->shortcodes[$name] = $callback;
    }

    /**
     * Compile content with shortcodes
     */
    public function compile(string $content): string
    {
        if (empty($content)) {
            return $content;
        }

        // Check cache if enabled
        if (config('shortcode.cache', true)) {
            $cacheKey = md5($content);
            if (isset($this->cache[$cacheKey])) {
                return $this->cache[$cacheKey];
            }
        }

        // Regex para shortcodes con contenido: [nombre param="value"]content[/nombre]
        $pattern = '/\[(\w+)([^\]]*)\](.*?)\[\/\1\]/s';

        $compiled = preg_replace_callback($pattern, function ($matches) {
            $name = $matches[1];
            $attributes = $this->parseAttributes($matches[2]);
            $content = $matches[3];

            if (isset($this->shortcodes[$name])) {
                try {
                    return call_user_func($this->shortcodes[$name], $attributes, $content);
                } catch (\Exception $e) {
                    \Log::error("Shortcode compilation error for [{$name}]: ".$e->getMessage());

                    return $matches[0];
                }
            }

            return $matches[0];  // Sin cambios si no existe
        }, $content);

        // Regex para shortcodes auto-cerrados: [nombre param="value" /]
        $selfClosingPattern = '/\[(\w+)([^\]]*?)\/\]/';

        $compiled = preg_replace_callback($selfClosingPattern, function ($matches) {
            $name = $matches[1];
            $attributes = $this->parseAttributes($matches[2]);

            if (isset($this->shortcodes[$name])) {
                try {
                    return call_user_func($this->shortcodes[$name], $attributes, '');
                } catch (\Exception $e) {
                    \Log::error("Shortcode compilation error for [{$name}]: ".$e->getMessage());

                    return $matches[0];
                }
            }

            return $matches[0];
        }, $compiled);

        // Cache result
        if (config('shortcode.cache', true)) {
            $this->cache[$cacheKey] = $compiled;
        }

        return $compiled;
    }

    /**
     * Parse shortcode attributes
     */
    protected function parseAttributes(string $text): array
    {
        $pattern = '/(\w+)=["\'](.*?)["\']/';
        $attributes = [];

        preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $attributes[$match[1]] = $match[2];
        }

        return $attributes;
    }

    /**
     * Strip all shortcodes from content
     */
    public function strip(string $content): string
    {
        // Remove shortcodes with content
        $content = preg_replace('/\[\w+[^\]]*\].*?\[\/\w+\]/s', '', $content);

        // Remove self-closing shortcodes
        $content = preg_replace('/\[\w+[^\]]*\/\]/', '', $content);

        return $content;
    }

    /**
     * Check if a shortcode is registered
     */
    public function has(string $name): bool
    {
        return isset($this->shortcodes[$name]);
    }

    /**
     * Get all registered shortcodes
     */
    public function all(): array
    {
        return array_keys($this->shortcodes);
    }

    /**
     * Clear cache
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }

    /**
     * Unregister a shortcode
     */
    public function unregister(string $name): void
    {
        unset($this->shortcodes[$name]);
    }
}
