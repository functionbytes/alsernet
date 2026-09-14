<?php

use Modules\Theme\Helpers\ThemeAssetHelper;

if (! function_exists('themeAsset')) {
    /**
     * Get URL to a theme asset
     *
     * @param  string  $path  Path relative to modules/Theme/public/theme/
     * @return string Full URL to the asset
     *
     * @example themeAsset('libs/select2/dist/css/select2.min.css')
     *          themeAsset('css/app.css')
     *          themeAsset('js/app.js')
     */
    function themeAsset(string $path): string
    {
        return ThemeAssetHelper::url($path);
    }
}

if (! function_exists('nav_item_enabled')) {
    /**
     * Este proyecto no trae el módulo "Modules" (gestor de on/off por item de
     * nav) de system, así que no hay tabla NavItemSetting para consultar.
     * Siempre visible, que es el mismo default documentado allá cuando no
     * existe una fila explícita.
     */
    function nav_item_enabled(string $key): bool
    {
        return true;
    }
}
