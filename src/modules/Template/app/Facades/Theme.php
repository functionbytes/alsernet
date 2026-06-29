<?php

namespace Modules\Template\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Modules\Template\Theme\Theme uses(string $theme)
 * @method static \Modules\Template\Theme\Theme theme(string $theme)
 * @method static \Modules\Template\Theme\Theme layout(string $layout)
 * @method static \Modules\Template\Theme\Theme of(string $view, array $args = [])
 * @method static \Modules\Template\Theme\Theme scope(string $view, array $args = [])
 * @method static string partial(string $view, array $args = [])
 * @method static string content()
 * @method static \Modules\Template\Theme\Asset asset()
 * @method static void fire(string $event, mixed $args = null)
 * @method static void fireEventGlobalAssets()
 * @method static void routes()
 * @method static string getThemeName()
 * @method static bool exists(?string $theme)
 * @method static string render()
 * @method static string styles(string $container = 'default')
 * @method static string scripts(string $container = 'footer')
 * @method static \Modules\Template\Theme\Theme set(string $region, mixed $value)
 * @method static mixed get(string $region, ?string $default = null)
 * @method static bool has(string $region)
 * @method static static addBodyAttributes(array $attributes)
 * @method static array getBodyAttributes()
 * @method static string bodyAttributes()
 * @method static static addHtmlAttributes(array $attributes)
 * @method static array getHtmlAttributes()
 * @method static string htmlAttributes()
 * @method static string|null getLogo(string $logoKey = 'logo')
 * @method static string|null getFavicon()
 * @method static string|null getSiteTitle()
 * @method static string|null getSiteCopyright()
 * @method static array getSocialLinks()
 * @method static string header()
 *
 * @see \Modules\Template\Theme\Theme
 */
class Theme extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Modules\Template\Theme\Theme::class;
    }
}
