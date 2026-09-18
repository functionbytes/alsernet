<?php

return [
    'name' => 'alvarez',
    'display_name' => 'Alvarez',
    'version' => '1.0.0',
    'theme_key' => '2170d1b6541ce293b34b6c1eb18e002b',
    'author' => [
        'name' => 'ADDIS Network',
        'email' => 'soporte@addis.es',
        'url' => 'https://www.addis.es/',
    ],
    'meta' => [
        'compatibility' => [
            'from' => '1.7.8.0',
            'to' => null,
        ],
        'available_layouts' => [
            'layout-full-width' => [
                'name' => 'Full Width',
                'description' => 'No side columns, ideal for distraction-free pages such as product pages.',
            ],
            'layout-both-columns' => [
                'name' => 'Three Columns',
                'description' => 'One large central column and 2 side columns.',
            ],
            'layout-left-column' => [
                'name' => 'Two Columns, small left column',
                'description' => 'Two columns with a small left column',
            ],
            'layout-right-column' => [
                'name' => 'Two Columns, small right column',
                'description' => 'Two columns with a small right column',
            ],
        ],
    ],
    'assets' => null,
    'dependencies' => [
        'modules' => [
            0 => 'appagebuilder',
            1 => 'leobootstrapmenu',
            2 => 'leoslideshow',
            3 => 'leoblog',
            4 => 'leofeature',
            5 => 'blockgrouptop',
            6 => 'leoquicklogin',
            7 => 'leoproductsearch',
            8 => 'leoextratab',
        ],
    ],
    'global_settings' => [
        'configuration' => [
            'PS_IMAGE_QUALITY' => 'png',
        ],
        'modules' => [
            'to_disable' => [
                0 => 'ps_contactinfo',
                1 => 'ps_mainmenu',
                2 => 'ps_imageslider',
                3 => 'ps_featuredproducts',
                4 => 'ps_banner',
                5 => 'ps_customtext',
                6 => 'productcomments',
                7 => 'ps_linklist',
                8 => 'blockwishlist',
            ],
        ],
        'hooks' => [
            'modules_to_hook' => [
                'displayNav1' => [
                    0 => 'appagebuilder',
                ],
                'displayNav2' => [
                    0 => 'appagebuilder',
                ],
                'displayTop' => [
                    0 => 'appagebuilder',
                ],
                'displayHome' => [
                    0 => 'leoblog',
                ],
                'displayFooterBefore' => [
                    0 => 'appagebuilder',
                ],
                'displayFooter' => [
                    0 => 'appagebuilder',
                ],
                'actionAdminBefore' => [
                    0 => 'appagebuilder',
                    1 => 'leobootstrapmenu',
                    2 => 'leoslideshow',
                    3 => 'leoblog',
                    4 => 'leofeature',
                    5 => 'blockgrouptop',
                    6 => 'leoquicklogin',
                    7 => 'leoproductsearch',
                    8 => 'leoextratab',
                ],
            ],
        ],
        'image_types' => [
            'cart_default' => [
                'width' => 125,
                'height' => 157,
                'scope' => [
                    0 => 'inventaries',
                ],
            ],
            'small_default' => [
                'width' => 98,
                'height' => 123,
                'scope' => [
                    0 => 'inventaries',
                    1 => 'categories',
                    2 => 'manufacturers',
                    3 => 'suppliers',
                ],
            ],
            'medium_default' => [
                'width' => 378,
                'height' => 472,
                'scope' => [
                    0 => 'inventaries',
                    1 => 'manufacturers',
                    2 => 'suppliers',
                ],
            ],
            'home_default' => [
                'width' => 378,
                'height' => 472,
                'scope' => [
                    0 => 'inventaries',
                ],
            ],
            'large_default' => [
                'width' => 800,
                'height' => 1000,
                'scope' => [
                    0 => 'inventaries',
                    1 => 'manufacturers',
                    2 => 'suppliers',
                ],
            ],
            'category_default' => [
                'width' => 480,
                'height' => 360,
                'scope' => [
                    0 => 'categories',
                ],
            ],
            'stores_default' => [
                'width' => 170,
                'height' => 115,
                'scope' => [
                    0 => 'stores',
                ],
            ],
            'manu_default' => [
                'width' => 130,
                'height' => 87,
                'scope' => [
                    0 => 'manufacturers',
                ],
            ],
        ],
    ],
    'theme_settings' => [
        'default_layout' => 'layout-full-width',
        'layouts' => [
            'category' => 'layout-left-column',
            'best-sales' => 'layout-left-column',
            'new-inventaries' => 'layout-left-column',
            'prices-drop' => 'layout-left-column',
            'contact' => 'layout-left-column',
            'manufacturer' => 'layout-left-column',
            'supplier' => 'layout-left-column',
            'module-leoblog-blog' => 'layout-left-column',
            'module-leoblog-category' => 'layout-left-column',
            'module-leoblog-list' => 'layout-left-column',
        ],
    ],
];
