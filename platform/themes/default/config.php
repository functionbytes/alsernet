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
    | [Notice] assets cannot inherit.
    |
    */
    'inherit' => null,

    /*
    |--------------------------------------------------------------------------
    | Template Options
    |--------------------------------------------------------------------------
    |
    | Configuration specific to this template
    |
    */
    'options' => [
        'sidebar_position' => 'right',
        'layout_type' => 'default',
        'max_width' => '1200px',
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
        ],
        'js' => [
            'main' => 'js/main.js',
        ],
    ],
];
