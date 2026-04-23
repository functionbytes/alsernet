<?php

namespace Modules\Seo\Services;

/**
 * Registry for sitemap post-callbacks that avoids storing closures
 * in Laravel config (which breaks config:cache).
 */
class SitemapCallbackRegistry
{
    /** @var array<int, callable> */
    private static array $callbacks = [];

    public static function register(callable $callback): void
    {
        self::$callbacks[] = $callback;
    }

    /**
     * @return array<int, callable>
     */
    public static function all(): array
    {
        return self::$callbacks;
    }

    public static function clear(): void
    {
        self::$callbacks = [];
    }
}
