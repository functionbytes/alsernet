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
    | SEO Score Goal
    |--------------------------------------------------------------------------
    |
    | Target SEO score (0-100). Pages meeting or exceeding this score are
    | considered to have achieved the goal.
    |
    */
    'score_goal' => env('SEO_SCORE_GOAL', 80),

    /*
    |--------------------------------------------------------------------------
    | Search Engine Verification Tags
    |--------------------------------------------------------------------------
    |
    | Verification meta tags for search engine webmaster tools.
    | Set these values in your .env file.
    |
    */
    'verification' => [
        'google' => env('SEO_GOOGLE_VERIFICATION', ''),
        'bing' => env('SEO_BING_VERIFICATION', ''),
        'pinterest' => env('SEO_PINTEREST_VERIFICATION', ''),
        'baidu' => env('SEO_BAIDU_VERIFICATION', ''),
        'yandex' => env('SEO_YANDEX_VERIFICATION', ''),
    ],

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

    /*
    |--------------------------------------------------------------------------
    | PageSpeed Insights API Key
    |--------------------------------------------------------------------------
    |
    | Optional Google PageSpeed Insights API key for Core Web Vitals analysis.
    | Without a key, requests are rate-limited. Set SEO_PAGESPEED_API_KEY in .env.
    |
    */
    'pagespeed_api_key' => env('SEO_PAGESPEED_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Cleanup Settings
    |--------------------------------------------------------------------------
    |
    | Configure automatic log cleanup retention periods.
    |
    */
    'cleanup' => [
        '404_logs_after_days' => 90,
        'audit_logs_after_days' => 180,
        'keep_audit_records_per_meta' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configure email notifications for SEO events.
    |
    */
    'notifications' => [
        'email' => env('SEO_NOTIFICATION_EMAIL', null),
        'score_drop_threshold' => 10,
        'notify_on_orphans' => false,
        'notify_on_redirect_chains' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Notifications
    |--------------------------------------------------------------------------
    |
    | Configure Slack and Discord webhook URLs for SEO event alerts.
    | Set the URLs in your .env file to enable notifications.
    |
    */
    'webhooks' => [
        'slack_url' => env('SEO_SLACK_WEBHOOK_URL', ''),
        'discord_url' => env('SEO_DISCORD_WEBHOOK_URL', ''),
        'notify_score_drop' => env('SEO_WEBHOOK_SCORE_DROP', true),
        'notify_redirect_chain' => env('SEO_WEBHOOK_REDIRECT_CHAIN', true),
        'notify_orphans' => env('SEO_WEBHOOK_ORPHANS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Roles that receive all SEO permissions when the seeder runs
    |--------------------------------------------------------------------------
    */
    'admin_roles' => ['super-settings', 'settings'],

    /*
    |--------------------------------------------------------------------------
    | IndexNow protocol (Bing, Yandex, Seznam)
    |--------------------------------------------------------------------------
    |
    | Permite notificar a los motores de búsqueda cuando publicas o
    | actualizas una URL para indexación casi inmediata. La `key` debe
    | tener entre 8 y 128 caracteres y se sirve en `/{key}.txt`.
    | Google NO soporta IndexNow.
    |
    */
    'indexnow' => [
        'enabled' => env('SEO_INDEXNOW_ENABLED', false),
        'key' => env('SEO_INDEXNOW_KEY', ''),
        'endpoint' => env('SEO_INDEXNOW_ENDPOINT', 'https://api.indexnow.org/indexnow'),
        'auto_submit' => env('SEO_INDEXNOW_AUTO_SUBMIT', true),
        'batch_size' => (int) env('SEO_INDEXNOW_BATCH_SIZE', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | llms.txt (guía para AI crawlers)
    |--------------------------------------------------------------------------
    |
    | Archivo en la raíz que ChatGPT/Claude/Perplexity usan para entender
    | la estructura del sitio y qué contenido es importante.
    | Ver: https://llmstxt.org
    |
    */
    'llms_txt' => [
        'enabled' => env('SEO_LLMS_TXT_ENABLED', true),
        'cache_ttl' => (int) env('SEO_LLMS_TXT_CACHE_TTL', 3600),
        'min_score' => (int) env('SEO_LLMS_TXT_MIN_SCORE', 70),
        'max_items' => (int) env('SEO_LLMS_TXT_MAX_ITEMS', 50),
        'intro' => env('SEO_LLMS_TXT_INTRO', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI crawlers conocidos (para auditar robots.txt)
    |--------------------------------------------------------------------------
    |
    | Usado por RobotsTxtController::aiCrawlersStatus() para reportar si
    | algún bot de IA está bloqueado accidentalmente. Los admins deciden
    | si permitir o bloquear cada uno editando robots.txt.
    |
    */
    /*
    |--------------------------------------------------------------------------
    | Content decay detection
    |--------------------------------------------------------------------------
    |
    | Parámetros del DetectContentDecayJob que se ejecuta semanalmente.
    | Una página se considera en "decay" cuando lleva N días sin updated_at
    | y sus datos de GSC sugieren pérdida de visibilidad.
    |
    */
    'content_decay' => [
        'days_stale' => (int) env('SEO_DECAY_DAYS_STALE', 90),
        'min_clicks_baseline' => (int) env('SEO_DECAY_MIN_CLICKS', 50),
        'position_drop' => (float) env('SEO_DECAY_POSITION_DROP', 5.0),
        'max_report_items' => (int) env('SEO_DECAY_MAX_REPORT', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Core Web Vitals tracking
    |--------------------------------------------------------------------------
    |
    | Configuración del beacon JS que recoge LCP/INP/CLS y los envía al
    | endpoint interno. `sample_rate` entre 0.0 y 1.0 controla qué fracción
    | de visitas reportan (0.1 = 10%).
    |
    */
    'web_vitals' => [
        'enabled' => env('SEO_WEB_VITALS_ENABLED', true),
        'sample_rate' => (float) env('SEO_WEB_VITALS_SAMPLE_RATE', 0.1),
        'retention_days' => (int) env('SEO_WEB_VITALS_RETENTION_DAYS', 90),
        'cdn' => env('SEO_WEB_VITALS_CDN', 'https://unpkg.com/web-vitals@4/dist/web-vitals.iife.js'),
    ],

    'ai_crawlers' => [
        'GPTBot' => 'OpenAI — entrena ChatGPT',
        'ChatGPT-User' => 'OpenAI — lecturas directas en ChatGPT',
        'OAI-SearchBot' => 'OpenAI — indexa para SearchGPT',
        'ClaudeBot' => 'Anthropic — entrena Claude',
        'Claude-Web' => 'Anthropic — lecturas directas en Claude',
        'PerplexityBot' => 'Perplexity AI — indexa para respuestas',
        'Google-Extended' => 'Google — entrena Gemini (no Search)',
        'Applebot-Extended' => 'Apple — entrena modelos de IA',
        'CCBot' => 'Common Crawl — datasets públicos',
        'Bytespider' => 'ByteDance / TikTok — entrena modelos',
        'anthropic-ai' => 'Anthropic — UA legacy',
        'cohere-ai' => 'Cohere — entrena modelos',
    ],
];
