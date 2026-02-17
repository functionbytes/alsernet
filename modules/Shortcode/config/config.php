<?php

return [
    'name' => 'Shortcode',

    /*
    |--------------------------------------------------------------------------
    | Enable Shortcode Processing
    |--------------------------------------------------------------------------
    |
    | Enable or disable shortcode processing globally.
    |
    */
    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Cache Compiled Shortcodes
    |--------------------------------------------------------------------------
    |
    | Enable caching of compiled shortcodes for better performance.
    |
    */
    'cache' => true,

    /*
    |--------------------------------------------------------------------------
    | Cache Duration
    |--------------------------------------------------------------------------
    |
    | Duration in seconds to cache compiled shortcodes.
    |
    */
    'cache_duration' => 3600,

    /*
    |--------------------------------------------------------------------------
    | Auto Register Default Shortcodes
    |--------------------------------------------------------------------------
    |
    | Automatically register the default shortcodes provided by the module.
    |
    */
    'auto_register' => true,

    /*
    |--------------------------------------------------------------------------
    | Default Shortcodes
    |--------------------------------------------------------------------------
    |
    | List of default shortcodes that are automatically registered.
    | Set to false to disable specific shortcodes.
    |
    */
    'default_shortcodes' => [
        'button' => true,
        'alert' => true,
        'columns' => true,
        'column' => true,
        'youtube' => true,
        'image' => true,
        'icon' => true,
        'badge' => true,
        'card' => true,
        'accordion' => true,
        'accordion-item' => true,
        'quote' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    |
    | How to handle shortcode errors:
    | - 'silent': Return original shortcode
    | - 'log': Log error and return original shortcode
    | - 'display': Display error message
    |
    */
    'error_handling' => 'log',

    /*
    |--------------------------------------------------------------------------
    | Maximum Nesting Level
    |--------------------------------------------------------------------------
    |
    | Maximum allowed nesting level for shortcodes to prevent infinite loops.
    |
    */
    'max_nesting_level' => 10,
];
