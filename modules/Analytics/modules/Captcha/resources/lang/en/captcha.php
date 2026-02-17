<?php

return [
    'name' => 'Captcha',
    'description' => 'CAPTCHA protection for forms',

    'settings' => [
        'title' => 'CAPTCHA Settings',
        'description' => 'Configure reCAPTCHA and Math CAPTCHA for form protection',
        'enable_recaptcha' => 'Enable reCAPTCHA',
        'recaptcha_warning' => 'Register your site at <a href="https://www.google.com/recaptcha/settings" target="_blank">Google reCAPTCHA</a> to get Site Key and Secret Key.',
        'type' => 'reCAPTCHA Type',
        'v2_description' => 'reCAPTCHA v2 - Checkbox verification',
        'v3_description' => 'reCAPTCHA v3 - Invisible, score-based',
        'site_key' => 'Site Key',
        'site_key_placeholder' => 'Enter your reCAPTCHA site key',
        'secret_key' => 'Secret Key',
        'secret_key_placeholder' => 'Enter your reCAPTCHA secret key',
        'score' => 'Minimum Score (v3)',
        'score_helper' => 'Score from 0.0 to 1.0 (0.5 recommended). Higher = stricter.',
        'math_captcha' => 'Math CAPTCHA',
        'enable_math_captcha' => 'Enable Math CAPTCHA',
        'enable_for_contact_form' => 'Enable for contact form',
        'enable_for_newsletter_form' => 'Enable for newsletter form',
        'save_settings' => 'Save Settings',
    ],

    'math' => [
        'label' => 'Solve this math problem',
        'placeholder' => 'Enter the result',
        'invalid' => 'Incorrect answer. Please try again.',
    ],

    'errors' => [
        'verification_failed' => 'CAPTCHA verification failed. Please try again.',
        'invalid_response' => 'Invalid CAPTCHA response.',
    ],
];
