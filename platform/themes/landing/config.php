<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Inherit from another template
    |--------------------------------------------------------------------------
    |
    | Set up inherit from another template if the file is not exists,
    | this works with "layouts", "partials" and "views"
    |
    */
    'inherit' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Template Options
    |--------------------------------------------------------------------------
    |
    | Configuration specific to this landing page template
    |
    */
    'options' => [
        'sidebar_position' => 'none',
        'layout_type' => 'landing',
        'max_width' => '100%',
        'header_style' => 'minimalist',
        'footer_style' => 'compact',
        'conversion_focused' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Template Assets
    |--------------------------------------------------------------------------
    |
    | Assets loaded for this specific template
    |
    */
    'assets' => [
        'css' => [
            'style' => 'css/style.css',
            'landing' => 'css/landing.css',
        ],
        'js' => [
            'main' => 'js/main.js',
            'landing' => 'js/landing.js',
        ],
    ],
];
