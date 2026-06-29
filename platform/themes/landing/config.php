<?php

return [
    'inherit' => 'default',
    'options' => [
        'sidebar_position' => 'none',
        'layout_type' => 'landing',
        'max_width' => '100%',
        'header_style' => 'minimalist',
        'footer_style' => 'compact',
        'conversion_focused' => true,
    ],
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
