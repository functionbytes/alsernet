<?php

return [
    'mailchimp' => [
        'api_key' => env('MAILCHIMP_API_KEY', ''),
        'server_prefix' => env('MAILCHIMP_SERVER_PREFIX', ''),
    ],
    'sendgrid' => [
        'api_key' => env('SENDGRID_API_KEY', ''),
    ],
    'fcm' => [
        'server_key' => env('FCM_SERVER_KEY', ''),
    ],
    'apn' => [
        'cert_path' => env('APN_CERT_PATH', ''),
        'key_path' => env('APN_KEY_PATH', ''),
        'passphrase' => env('APN_PASSPHRASE', ''),
    ],
];
