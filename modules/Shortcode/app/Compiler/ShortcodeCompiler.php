<?php

namespace Modules\Shortcode\Compiler;

class ShortcodeCompiler
{
    /**
     * Registered shortcodes (name => callable)
     */
    protected array $shortcodes = [];

    /**
     * Shortcode metadata (name => array)
     */
    protected array $metadata = [];

    /**
     * Cache for compiled shortcodes
     */
    protected array $cache = [];

    /**
     * Register a new shortcode with optional metadata
     *
     * @param  array{description?: string, example?: string, attributes?: array<string, string>}  $meta
     */
    public function register(string $name, callable $callback, array $meta = []): void
    {
        $this->shortcodes[$name] = $callback;
        $this->metadata[$name] = $meta;
    }

    /**
     * Compile content with shortcodes
     */
    public function compile(string $content): string
    {
        if (empty($content)) {
            return $content;
        }

        // Fast-path: skip expensive regex if no shortcode brackets present
        if (! str_contains($content, '[')) {
            return $content;
        }

        // Declare before any conditional to avoid undefined variable in PHP 8
        $cacheKey = null;

        if (config('shortcode.cache', true)) {
            $cacheKey = md5($content);
            if (isset($this->cache[$cacheKey])) {
                return $this->cache[$cacheKey];
            }
        }

        // Regex para shortcodes con contenido: [nombre param="value"]content[/nombre]
        // [\w-]+ permite guiones en nombres como contact-form, accordion-item
        $pattern = '/\[([\w-]+)([^\]]*)\](.*?)\[\/\1\]/s';

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

        // Regex para shortcodes sin cierre explícito: [nombre param="value"]
        // Solo actúa sobre shortcodes registrados para no afectar HTML ni otros corchetes
        $barePattern = '/\[([\w-]+)([^\]]*)\]/';

        $compiled = preg_replace_callback($barePattern, function ($matches) {
            $name = $matches[1];

            if (! isset($this->shortcodes[$name])) {
                return $matches[0];
            }

            $attributes = $this->parseAttributes($matches[2]);

            try {
                return call_user_func($this->shortcodes[$name], $attributes, '');
            } catch (\Exception $e) {
                \Log::error("Shortcode compilation error for [{$name}]: ".$e->getMessage());

                return $matches[0];
            }
        }, $compiled);

        // Cache result only if caching was enabled (cacheKey is not null)
        if ($cacheKey !== null) {
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
     * Get all registered shortcode names
     */
    public function all(): array
    {
        return array_keys($this->shortcodes);
    }

    /**
     * Get metadata for all registered shortcodes
     *
     * @return array<string, array{name: string, description: string, example: string, attributes: array<string, string>}>
     */
    public function getRegistered(): array
    {
        $result = [];

        foreach ($this->shortcodes as $name => $_callback) {
            $meta = $this->metadata[$name] ?? [];
            $result[] = [
                'name' => $name,
                'description' => $meta['description'] ?? '',
                'example' => $meta['example'] ?? "[{$name}][/{$name}]",
                'attributes' => $meta['attributes'] ?? [],
            ];
        }

        return $result;
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
