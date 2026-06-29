<?php

namespace Modules\Shortcode\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Shortcode\Compiler\ShortcodeCompiler;

/**
 * @method static void register(string $name, callable $callback, array $meta = [])
 * @method static string compile(string $content)
 * @method static string compileWithContext(string $content, ?array $allowed = null)
 * @method static string strip(string $content)
 * @method static bool has(string $name)
 * @method static array all()
 * @method static array getRegistered()
 * @method static void clearCache()
 * @method static bool forgetCachedContent(string $content)
 * @method static void unregister(string $name)
 * @method static void removeAll()
 * @method static string getRegex()
 * @method static array find(string $content)
 * @method static array atts(array $defaults, array $atts)
 * @method static \Modules\Shortcode\Compiler\ShortcodeCompiler withCspNonce(?string $nonce)
 * @method static string|null getCspNonce()
 *
 * @see ShortcodeCompiler
 */
class Shortcode extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'shortcode';
    }
}
