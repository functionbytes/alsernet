<?php

use Illuminate\Support\Str;
use Modules\Chat\Helpers\ChatAssetHelper;

if (! function_exists('chatAsset')) {
    /**
     * Get URL to a chat asset
     * Loads directly from module via symlink - no publication required
     *
     * @param  string  $path  Path relative to modules/Chat/public/
     * @return string Full URL to the asset (/chat-module/...)
     *
     * @example chatAsset('css/chat.css')   → /chat-module/css/chat.css
     *          chatAsset('js/app.js')          → /chat-module/js/app.js
     */
    function chatAsset(string $path): string
    {
        return ChatAssetHelper::url($path);
    }
}

if (! function_exists('stripTags')) {
    /**
     * Strip HTML tags and truncate to the given length.
     *
     * @param  string|null  $html  Raw content (may contain HTML).
     * @param  int  $limit  Max characters (default 100).
     * @param  string  $end  Appended when truncated (default '...').
     * @param  string  $empty  Returned when $html is blank (default '').
     */
    function stripTags(?string $html, int $limit = 100, string $end = '...', string $empty = ''): string
    {
        if (blank($html)) {
            return $empty;
        }

        return Str::limit(strip_tags($html), $limit, $end);
    }
}

if (! function_exists('chatAssets')) {
    /**
     * Get multiple chat asset URLs
     *
     * @param  array  $paths  Array of paths relative to modules/Chat/public/
     * @return array Array of URLs
     */
    function chatAssets(array $paths): array
    {
        return ChatAssetHelper::urls($paths);
    }
}
