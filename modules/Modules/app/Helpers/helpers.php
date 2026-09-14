<?php

use Modules\Modules\Models\NavItemSetting;

if (! function_exists('nav_item_enabled')) {
    /**
     * Check whether a navigation entry (mini-nav module, sidebar, or sidebar
     * item) is enabled. Defaults to true when no row exists, so every entry
     * stays visible until an admin explicitly hides it.
     */
    function nav_item_enabled(string $key): bool
    {
        $flags = cache()->remember('module_nav_flags', now()->addMinutes(10), function () {
            try {
                return NavItemSetting::allAsArray();
            } catch (Throwable) {
                return [];
            }
        });

        return $flags[$key] ?? true;
    }
}
