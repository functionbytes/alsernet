<?php

namespace Modules\Shortcode\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void register(string $name, callable $callback)
 * @method static string compile(string $content)
 * @method static string strip(string $content)
 * @method static bool has(string $name)
 * @method static array all()
 * @method static void clearCache()
 * @method static void unregister(string $name)
 *
 * @see \Modules\Shortcode\Compiler\ShortcodeCompiler
 */
class Shortcode extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'shortcode';
    }
}
