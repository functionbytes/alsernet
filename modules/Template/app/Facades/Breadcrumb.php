<?php

namespace Modules\Template\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool enabled()
 * @method static \Modules\Template\Theme\Breadcrumb add(string|array|null $label, ?string $url = '')
 * @method static string render(string $view = 'template::partials.breadcrumb')
 * @method static array getCrumbs()
 *
 * @see \Modules\Template\Theme\Breadcrumb
 */
class Breadcrumb extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Modules\Template\Theme\Breadcrumb::class;
    }
}
