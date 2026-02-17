<?php

return [
    'name' => 'Page',

    /*
    |--------------------------------------------------------------------------
    | Page Templates
    |--------------------------------------------------------------------------
    |
    | Define available page templates. The key is the template identifier
    | and the value is the display name.
    |
    */
    'templates' => [
        'default' => 'Default',
        'full-width' => 'Full Width',
        'no-sidebar' => 'No Sidebar',
        'landing' => 'Landing Page',
        'contact' => 'Contact Page',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Number of pages per page in listings.
    |
    */
    'per_page' => 20,

    /*
    |--------------------------------------------------------------------------
    | Media Settings
    |--------------------------------------------------------------------------
    |
    | Configure media library settings for pages.
    |
    */
    'media' => [
        'max_file_size' => 2048, // KB
        'allowed_mimes' => ['jpeg', 'png', 'jpg', 'gif', 'webp'],
    ],

    /*
    |--------------------------------------------------------------------------
    | SEO Settings
    |--------------------------------------------------------------------------
    |
    | Default SEO settings for pages.
    |
    */
    'seo' => [
        'title_max_length' => 60,
        'description_max_length' => 160,
        'keywords_max_length' => 255,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configure caching behavior for pages.
    |
    */
    'cache' => [
        'enabled' => env('PAGE_CACHE_ENABLED', true),
        'ttl' => env('PAGE_CACHE_TTL', 3600), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Settings
    |--------------------------------------------------------------------------
    |
    | Configure full-text search and live search functionality.
    |
    */
    'search' => [
        'enabled' => env('PAGE_SEARCH_ENABLED', true),
        'fulltext' => env('PAGE_SEARCH_FULLTEXT', true), // Use FULLTEXT indexes
        'live_search' => env('PAGE_SEARCH_LIVE', true), // Enable live autocomplete search
        'min_length' => 2, // Minimum characters for search
        'debounce_ms' => 300, // Debounce time in milliseconds
        'results_per_page' => 10,
        'quick_search_limit' => 5, // Number of results for autocomplete
    ],

    /*
    |--------------------------------------------------------------------------
    | Enable Live Search
    |--------------------------------------------------------------------------
    |
    | Enable or disable the live search box with autocomplete.
    | When disabled, a basic search form will be used instead.
    |
    */
    'enable_live_search' => env('PAGE_ENABLE_LIVE_SEARCH', true),
];
