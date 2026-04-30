<?php

namespace Modules\Chat\Helpers;

/**
 * Chat Asset Helper
 *
 * Provides functions to access chat assets directly from the module
 * via symlink (modules/Chat/public → public/chat-module)
 * No publication required - assets load directly from module
 */
class ChatAssetHelper
{
    /**
     * Get URL to a chat asset
     *
     * @param  string  $path  Path relative to modules/Chat/public/
     * @return string Full URL to the asset
     *
     * @example chatAsset('css/chat.css')
     *          chatAsset('js/app.js')
     */
    public static function url(string $path): string
    {
        // Load directly from module via symlink (no publication needed)
        return '/chat-module/'.$path;
    }

    /**
     * Get multiple chat asset URLs
     *
     * @param  array  $paths  Array of paths
     * @return array Array of URLs
     */
    public static function urls(array $paths): array
    {
        return array_map(fn ($path) => self::url($path), $paths);
    }
}
