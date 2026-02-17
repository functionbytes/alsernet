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
    | Configuration specific to this template
    |
    */
    'options' => [
        'sidebar_position' => 'none',
        'layout_type' => 'full-width',
        'max_width' => '100%',
        'padding' => 'normal',
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
            'full-width' => 'css/full-width.css',
        ],
        'js' => [
            'main' => 'js/main.js',
        ],
    ],
];
