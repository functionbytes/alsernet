<?php

namespace Modules\Template\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Support\HtmlString googleFonts(string $font)
 * @method static bool isRtlEnabled()
 * @method static string getHomepageUrl()
 * @method static string renderIcon(string $name, ?string $size = null, array $attributes = [])
 * @method static array|string|null clean(array|string|null $dirty, array|string|null $config = null)
 * @method static \Illuminate\Support\HtmlString html(array|string|null $dirty, array|string|null $config = null)
 *
 * @see \Modules\Template\Helpers\BaseHelper
 */
class BaseHelper extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Modules\Template\Helpers\BaseHelper::class;
    }
}
