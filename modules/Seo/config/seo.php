<?php

return [
    /*
     * The default configurations to be used by the meta generator.
     */
    'meta' => [
        'defaults' => [
            'title' => 'Default Title',
            'titleBefore' => false,
            'description' => 'Default description',
            'separator' => ' - ',
            'keywords' => [],
            'canonical' => false,
            'robots' => false,
        ],
        'webmaster_tags' => [
            'google' => null,
            'bing' => null,
            'alexa' => null,
            'pinterest' => null,
            'yandex' => null,
            'norton' => null,
        ],
        'add_notranslate_class' => false,
    ],

    /*
     * The default configurations to be used by the opengraph generator.
     */
    'opengraph' => [
        'defaults' => [
            'title' => 'Default Title',
            'description' => 'Default description',
            'url' => false,
            'type' => 'website',
            'site_name' => null,
            'images' => [],
        ],
    ],

    /*
     * The default values to be used by the twitter cards generator.
     */
    'twitter' => [
        'defaults' => [
            'card' => 'summary_large_image',
            'site' => null,
        ],
    ],

    /*
     * The default configurations to be used by the json-ld generator.
     */
    'json-ld' => [
        'defaults' => [
            'title' => 'Default Title',
            'description' => 'Default description',
            'url' => false,
            'type' => 'WebPage',
            'images' => [],
        ],
    ],
];
