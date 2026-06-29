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
        'contact-form' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    |
    | How to handle shortcode errors:
    | - 'silent':  Return original shortcode
    | - 'log':     Log error and return original shortcode (default)
    | - 'display': Reemplaza el shortcode fallido con un span.shortcode-error
    | - 'throw':   Lanza ShortcodeCompilationException (útil en tests)
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

    /*
    |--------------------------------------------------------------------------
    | Track Usage Stats
    |--------------------------------------------------------------------------
    |
    | Si true, registra en cache cuántas veces se compila cada shortcode.
    | Consultable en /panel/setting/shortcodes y via Shortcode::stats().
    |
    */
    'track_usage' => true,

    /*
    |--------------------------------------------------------------------------
    | Output Purify
    |--------------------------------------------------------------------------
    |
    | Si un shortcode declara meta['purify'] = true y HTMLPurifier está
    | disponible, el output se sanitiza antes de retornar.
    |
    */
    'purify_enabled' => env('SHORTCODE_PURIFY', true),

    /*
    |--------------------------------------------------------------------------
    | Validate Sources
    |--------------------------------------------------------------------------
    |
    | Tablas/columnas a escanear con `php artisan shortcode:validate` para
    | detectar shortcodes huérfanos (usados pero no registrados).
    |
    */
    'validate_sources' => [
        ['table' => 'pages', 'id' => 'id', 'column' => 'content'],
        ['table' => 'blog_posts', 'id' => 'id', 'column' => 'content'],
        ['table' => 'template_shortcodes', 'id' => 'id', 'column' => 'content'],
    ],
];
