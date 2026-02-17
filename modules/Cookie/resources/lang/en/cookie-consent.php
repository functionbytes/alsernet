<?php

return [
    'name' => 'Cookie Consent',
    'description' => 'GDPR-compliant cookie consent management',

    'theme_options' => [
        'name' => 'Cookie Consent',
        'description' => 'Cookie consent settings',
        'enable' => 'Enable cookie consent',
        'message' => 'Message',
        'button_text' => 'Button text',
        'max_width' => 'Max width (px)',
        'background_color' => 'Background color',
        'text_color' => 'Text color',
        'learn_more_url' => 'Learn more URL',
        'learn_more_text' => 'Learn more text',
        'style' => 'Style',
        'full_width' => 'Full width',
        'minimal' => 'Minimal',
        'show_reject_button' => 'Show reject button',
        'show_reject_button_helper' => 'When enabled, users will see a button to reject all non-essential cookies.',
        'show_customize_button' => 'Show customize preferences button',
        'show_customize_button_helper' => 'When enabled, users will see a button to customize their cookie preferences, making it GDPR compliant.',
    ],

    'message' => 'Your experience on this site will be improved by allowing cookies.',
    'button_text' => 'Accept cookies',
    'reject_text' => 'Reject',
    'customize_text' => 'Customize preferences',
    'save_text' => 'Save preferences',

    'cookie_categories' => [
        'essential' => [
            'name' => 'Essential',
            'description' => 'These cookies are essential for the website to function properly and cannot be disabled.',
        ],
        'analytics' => [
            'name' => 'Analytics',
            'description' => 'These cookies help us understand how visitors interact with the website by collecting and reporting information anonymously.',
        ],
        'marketing' => [
            'name' => 'Marketing',
            'description' => 'These cookies are used to track visitors across websites to display relevant and engaging advertisements.',
        ],
    ],

    'settings' => [
        'title' => 'Cookie Consent Settings',
        'general' => 'General Settings',
        'appearance' => 'Appearance',
        'categories' => 'Cookie Categories',
        'integrations' => 'Integrations',
    ],
];
