<?php

namespace Modules\Template\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Template\Services\MenuService;

/**
 * @method static string renderMenu(string $location, array $attributes = [])
 * @method static string renderMenuLocation(string $location, array $attributes = [])
 *
 * @see MenuService
 */
class Menu extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return MenuService::class;
    }
}
