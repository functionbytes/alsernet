<?php

namespace Modules\Template\Helpers;

use Modules\Template\Services\MenuService;

if (! function_exists('render_menu')) {
    /**
     * Render a menu by location.
     */
    function render_menu(string $location, array $attributes = []): string
    {
        $menuService = app(MenuService::class);

        return $menuService->renderMenu($location, $attributes);
    }
}

if (! function_exists('menu')) {
    /**
     * Get a menu by location.
     */
    function menu(string $location)
    {
        return \Modules\Template\Models\Menu::where('location', $location)
            ->where('status', true)
            ->with(['items' => function ($query) {
                $query->with('children');
            }])
            ->first();
    }
}
