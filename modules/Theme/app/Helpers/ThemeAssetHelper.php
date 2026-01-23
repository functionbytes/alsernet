<?php

namespace Modules\Theme\Helpers;

/**
 * Theme Asset Helper
 *
 * Provides functions to access theme assets directly from the module
 * without publishing them to public/
 */
class ThemeAssetHelper
{
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
    public static function url(string $path): string
    {
        return route('theme.asset', ['path' => $path]);
    }

    /**
     * Get multiple theme asset URLs
     *
     * @param array $paths Array of paths
     * @return array Array of URLs
     */
    public static function urls(array $paths): array
    {
        return array_map(fn($path) => self::url($path), $paths);
    }
}
