<?php

return [
    /*
     * Enable or disable cookie consent globally
     */
    'enabled' => env('COOKIE_CONSENT_ENABLED', true),

    /*
     * The name of the cookie in which we store if the user
     * has agreed to accept the conditions.
     */
    'cookie_name' => env('COOKIE_CONSENT_NAME', 'cookie_for_consent'),

    /*
     * Set the cookie duration in days. Default is 365 * 20.
     */
    'cookie_lifetime' => env('COOKIE_CONSENT_LIFETIME', 365 * 20),

    /*
     * Cookie consent version (stored in consent logs)
     */
    'version' => '1.0',

    /*
     * Cookie consent style: 'full-width' or 'minimal'
     */
    'style' => env('COOKIE_CONSENT_STYLE', 'full-width'),

    /*
     * Cookie consent message
     */
    'message' => 'Your experience on this site will be improved by allowing cookies.',

    /*
     * Cookie consent button text
     */
    'button_text' => 'Accept cookies',

    /*
     * Cookie consent reject button text
     */
    'reject_text' => 'Reject',

    /*
     * Cookie consent customize button text
     */
    'customize_text' => 'Customize preferences',

    /*
     * Cookie consent save button text
     */
    'save_text' => 'Save preferences',

    /*
     * Learn more URL (optional)
     */
    'learn_more_url' => '/cookie-policy',

    /*
     * Learn more link text (optional)
     */
    'learn_more_text' => 'Learn more',

    /*
     * Maximum width of the cookie consent banner (in pixels)
     */
    'max_width' => 1170,

    /*
     * Cookie categories for GDPR compliance
     */
    'cookie_categories' => [
        'essential' => [
            'required' => true,
            'name' => 'Essential',
            'description' => 'These cookies are essential for the website to function properly.',
        ],
        'analytics' => [
            'required' => false,
            'name' => 'Analytics',
            'description' => 'These cookies help us understand how visitors interact with the website.',
        ],
        'marketing' => [
            'required' => false,
            'name' => 'Marketing',
            'description' => 'These cookies are used to deliver personalized advertisements.',
        ],
    ],

];
