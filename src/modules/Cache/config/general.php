<?php

return [
    'admin_menu_enabled' => env('CACHE_ADMIN_MENU', false),
    'frontend_menu_enabled' => env('CACHE_FRONTEND_MENU', false),
    'user_avatars_enabled' => env('CACHE_USER_AVATARS', false),
    'sitemap_enabled' => env('CACHE_SITEMAP', false),
    'sitemap_ttl' => env('CACHE_SITEMAP_TTL', 1440),
];
