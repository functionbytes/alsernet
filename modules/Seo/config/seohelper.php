<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Title Suffix
    |--------------------------------------------------------------------------
    |
    | This suffix will be appended to all page titles unless explicitly
    | disabled. Typically includes your site name.
    |
    */
    'default_title_suffix' => ' - '.config('app.name', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Default Meta Description
    |--------------------------------------------------------------------------
    |
    | Default description to use when no specific description is provided.
    |
    */
    'default_description' => 'Welcome to '.config('app.name', 'our website'),

    /*
    |--------------------------------------------------------------------------
    | Default Open Graph Image
    |--------------------------------------------------------------------------
    |
    | Default image to use for Open Graph tags when no specific image is set.
    | Should be an absolute URL.
    |
    */
    'default_og_image' => env('SEO_DEFAULT_OG_IMAGE', asset('images/og-default.jpg')),

    /*
    |--------------------------------------------------------------------------
    | Twitter Site Handle
    |--------------------------------------------------------------------------
    |
    | Your Twitter/X site handle (e.g., @yoursite).
    |
    */
    'twitter_site' => env('SEO_TWITTER_SITE', '@yourhandle'),

    /*
    |--------------------------------------------------------------------------
    | Default Robots Directive
    |--------------------------------------------------------------------------
    |
    | Default robots meta tag value. Common values:
    | - index,follow (allow indexing and following links)
    | - noindex,nofollow (prevent indexing and following)
    | - noindex,follow (don't index but follow links)
    | - index,nofollow (index but don't follow links)
    |
    */
    'default_robots' => 'index,follow',

    /*
    |--------------------------------------------------------------------------
    | Open Graph Defaults
    |--------------------------------------------------------------------------
    |
    | Default values for Open Graph tags.
    |
    */
    'og_defaults' => [
        'type' => 'website',
        'locale' => 'en_US',
        'site_name' => config('app.name', 'Laravel'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Twitter Card Defaults
    |--------------------------------------------------------------------------
    |
    | Default values for Twitter Card tags.
    | Available types: summary, summary_large_image, app, player
    |
    */
    'twitter_defaults' => [
        'card' => 'summary_large_image',
    ],

    /*
    |--------------------------------------------------------------------------
    | Title Length Limits
    |--------------------------------------------------------------------------
    |
    | Recommended character limits for titles in different contexts.
    | These are not enforced but provided as guidelines.
    |
    */
    'title_limits' => [
        'seo' => 60,         // Google typically displays 50-60 characters
        'og' => 95,          // Facebook truncates at ~95 characters
        'twitter' => 70,     // Twitter truncates at ~70 characters
    ],

    /*
    |--------------------------------------------------------------------------
    | Description Length Limits
    |--------------------------------------------------------------------------
    |
    | Recommended character limits for descriptions in different contexts.
    | These are not enforced but provided as guidelines.
    |
    */
    'description_limits' => [
        'seo' => 160,        // Google typically displays 150-160 characters
        'og' => 200,         // Facebook displays up to 200 characters
        'twitter' => 200,    // Twitter displays up to 200 characters
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Size Requirements
    |--------------------------------------------------------------------------
    |
    | Recommended image dimensions for social sharing.
    |
    */
    'image_sizes' => [
        'og' => [
            'width' => 1200,
            'height' => 630,
            'ratio' => '1.91:1',
        ],
        'twitter' => [
            'summary' => [
                'width' => 120,
                'height' => 120,
                'ratio' => '1:1',
            ],
            'summary_large_image' => [
                'width' => 1200,
                'height' => 675,
                'ratio' => '16:9',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-generate Descriptions
    |--------------------------------------------------------------------------
    |
    | Automatically generate descriptions from content if none is provided.
    |
    */
    'auto_generate_description' => true,
    'auto_description_length' => 160,

    /*
    |--------------------------------------------------------------------------
    | Canonical URL Settings
    |--------------------------------------------------------------------------
    |
    | Settings for automatic canonical URL generation.
    |
    */
    'canonical' => [
        'enabled' => true,
        'force_https' => true,
        'force_trailing_slash' => false,
        'remove_query_string' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | JSON-LD Schema Support
    |--------------------------------------------------------------------------
    |
    | Enable or disable automatic JSON-LD schema generation.
    |
    */
    'json_ld' => [
        'enabled' => true,
        'organization' => [
            'name' => config('app.name', 'Laravel'),
            'logo' => asset('images/logo.png'),
            'url' => config('app.url'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Schema.org Structured Data Configuration
    |--------------------------------------------------------------------------
    |
    | Configure Schema.org structured data settings for JSON-LD output.
    | Follows Schema.org v13 specification.
    |
    */
    'schema' => [
        /*
        | Enable/disable all structured data generation
        */
        'enabled' => env('SEO_SCHEMA_ENABLED', true),

        /*
        | Organization schema configuration
        */
        'organization' => [
            'type' => 'Organization', // Organization, Corporation, LocalBusiness, etc.
            'name' => env('SEO_ORGANIZATION_NAME', config('app.name', 'Laravel')),
            'url' => env('SEO_ORGANIZATION_URL', config('app.url')),
            'logo' => env('SEO_ORGANIZATION_LOGO', asset('images/logo.png')),
            'description' => env('SEO_ORGANIZATION_DESCRIPTION', null),
            'email' => env('SEO_ORGANIZATION_EMAIL', null),
            'phone' => env('SEO_ORGANIZATION_PHONE', null),

            // Address
            'address' => [
                'street' => env('SEO_ORGANIZATION_ADDRESS_STREET', null),
                'city' => env('SEO_ORGANIZATION_ADDRESS_CITY', null),
                'region' => env('SEO_ORGANIZATION_ADDRESS_REGION', null),
                'postal_code' => env('SEO_ORGANIZATION_ADDRESS_POSTAL', null),
                'country' => env('SEO_ORGANIZATION_ADDRESS_COUNTRY', null),
            ],

            // Social media profiles
            'social_profiles' => [
                'facebook' => env('SEO_SOCIAL_FACEBOOK', null),
                'twitter' => env('SEO_SOCIAL_TWITTER', null),
                'instagram' => env('SEO_SOCIAL_INSTAGRAM', null),
                'linkedin' => env('SEO_SOCIAL_LINKEDIN', null),
                'youtube' => env('SEO_SOCIAL_YOUTUBE', null),
            ],
        ],

        /*
        | Local Business schema configuration
        */
        'local_business' => [
            'type' => 'LocalBusiness', // Restaurant, Store, etc.
            'name' => env('SEO_BUSINESS_NAME', config('app.name', 'Laravel')),
            'url' => env('SEO_BUSINESS_URL', config('app.url')),
            'image' => env('SEO_BUSINESS_IMAGE', asset('images/business.jpg')),
            'phone' => env('SEO_BUSINESS_PHONE', null),
            'email' => env('SEO_BUSINESS_EMAIL', null),
            'price_range' => env('SEO_BUSINESS_PRICE_RANGE', null), // e.g., "$$"

            // Address
            'address' => [
                'street' => env('SEO_BUSINESS_ADDRESS_STREET', null),
                'city' => env('SEO_BUSINESS_ADDRESS_CITY', null),
                'region' => env('SEO_BUSINESS_ADDRESS_REGION', null),
                'postal_code' => env('SEO_BUSINESS_ADDRESS_POSTAL', null),
                'country' => env('SEO_BUSINESS_ADDRESS_COUNTRY', null),
            ],

            // Geo coordinates
            'geo' => [
                'latitude' => env('SEO_BUSINESS_GEO_LAT', null),
                'longitude' => env('SEO_BUSINESS_GEO_LNG', null),
            ],

            // Opening hours - array of specifications
            'opening_hours' => [
                // Example:
                // [
                //     'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                //     'opens' => '09:00',
                //     'closes' => '17:00',
                // ],
                // [
                //     'days' => ['Saturday'],
                //     'opens' => '10:00',
                //     'closes' => '14:00',
                // ],
            ],
        ],

        /*
        | Default currency for product schemas
        */
        'default_currency' => env('SEO_DEFAULT_CURRENCY', 'USD'),

        /*
        | Schema types mapping for models
        | You can override these in your models by implementing getSchemaType()
        */
        'model_types' => [
            'page' => 'WebPage',
            'post' => 'BlogPosting',
            'article' => 'Article',
            'product' => 'Product',
            'event' => 'Event',
        ],

        /*
        | Auto-generate schemas for specific model types
        */
        'auto_generate' => [
            'breadcrumbs' => true,
            'organization' => true,
            'webpage' => true,
        ],
    ],
];
