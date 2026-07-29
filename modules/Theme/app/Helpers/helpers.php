<?php

use Modules\Theme\Helpers\ThemeAssetHelper;

if (!function_exists('themeAsset')) {
    /**
     * Get URL to a theme asset
     *
     * @param string $path Path relative to modules/Theme/public/theme/
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

if (!function_exists('themeAssets')) {
    /**
     * Get multiple theme asset URLs
     *
     * @param array $paths Array of paths relative to modules/Theme/public/theme/
     * @return array Array of URLs
     */
    function themeAssets(array $paths): array
    {
        return ThemeAssetHelper::urls($paths);
    }
}
