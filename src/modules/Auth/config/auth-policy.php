<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Password Policy
    |--------------------------------------------------------------------------
    */
    'password' => [
        'min_length' => env('AUTH_PWD_MIN_LENGTH', 10),
        'require_uppercase' => env('AUTH_PWD_UPPERCASE', true),
        'require_lowercase' => env('AUTH_PWD_LOWERCASE', true),
        'require_numbers' => env('AUTH_PWD_NUMBERS', true),
        'require_symbols' => env('AUTH_PWD_SYMBOLS', true),
        'reject_compromised' => env('AUTH_PWD_REJECT_COMPROMISED', true),

        // Password history: block reuse of last N passwords
        'history_count' => env('AUTH_PWD_HISTORY', 5),

        // Expiry in days (0 disables expiry)
        'expires_in_days' => env('AUTH_PWD_EXPIRES_DAYS', 0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Account Lockout (persistent, cross-device)
    |--------------------------------------------------------------------------
    | After N consecutive failed logins the account is locked until the
    | configured duration passes, independent of IP/rate limits.
    */
    'lockout' => [
        'enabled' => env('AUTH_LOCKOUT_ENABLED', true),
        'max_attempts' => env('AUTH_LOCKOUT_MAX_ATTEMPTS', 10),
        'duration_minutes' => env('AUTH_LOCKOUT_DURATION', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Magic Link Login
    |--------------------------------------------------------------------------
    */
    'magic_link' => [
        'enabled' => env('AUTH_MAGIC_LINK_ENABLED', true),
        'expires_in_minutes' => env('AUTH_MAGIC_LINK_TTL', 15),
        'rate_limit_per_email' => env('AUTH_MAGIC_LINK_RATE', 3),
        'rate_limit_window_minutes' => env('AUTH_MAGIC_LINK_WINDOW', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting (Redis backed)
    |--------------------------------------------------------------------------
    | Combined user_id + IP rate limiters. Overrides default IP-only limits.
    */
    'rate_limits' => [
        'login' => [
            'max_attempts' => env('AUTH_LOGIN_MAX_ATTEMPTS', 5),
            'decay_seconds' => env('AUTH_LOGIN_DECAY', 60),
        ],
        'two_factor' => [
            'max_attempts' => env('AUTH_2FA_MAX_ATTEMPTS', 5),
            'decay_seconds' => env('AUTH_2FA_DECAY', 300),
        ],
        'password_reset' => [
            'max_attempts' => env('AUTH_PWD_RESET_MAX_ATTEMPTS', 3),
            'decay_seconds' => env('AUTH_PWD_RESET_DECAY', 900),
        ],
        'customer_identity' => [
            'max_attempts' => env('AUTH_CUSTOMER_IDENTITY_MAX_ATTEMPTS', 3),
            'decay_seconds' => env('AUTH_CUSTOMER_IDENTITY_DECAY', 60),
        ],
        'customer_identity_request' => [
            'max_attempts' => env('AUTH_CUSTOMER_IDENTITY_REQUEST_MAX_ATTEMPTS', 5),
            'decay_seconds' => env('AUTH_CUSTOMER_IDENTITY_REQUEST_DECAY', 600),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Device Tracking
    |--------------------------------------------------------------------------
    */
    'devices' => [
        'enabled' => env('AUTH_DEVICE_TRACKING', true),
        'alert_new_device' => env('AUTH_ALERT_NEW_DEVICE', true),
        'trust_duration_days' => env('AUTH_DEVICE_TRUST_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Impersonation
    |--------------------------------------------------------------------------
    */
    'impersonation' => [
        'enabled' => env('AUTH_IMPERSONATION', true),
        'max_duration_minutes' => env('AUTH_IMPERSONATION_MAX_MINUTES', 60),
        'required_permission' => 'auth.impersonate',
    ],

    /*
    |--------------------------------------------------------------------------
    | Lock Screen
    |--------------------------------------------------------------------------
    | Locks session after inactivity; re-auth with password without logout.
    */
    'lock_screen' => [
        'enabled' => env('AUTH_LOCK_SCREEN', true),
        'inactivity_minutes' => env('AUTH_LOCK_INACTIVITY', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Reset Token
    |--------------------------------------------------------------------------
    */
    'reset_token' => [
        'expiry_hours' => env('AUTH_RESET_TOKEN_HOURS', 48),
        'daily_tries' => env('AUTH_RESET_DAILY_TRIES', 3),
    ],
];
