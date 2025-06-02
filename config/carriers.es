return [
    'default' => env('DEFAULT_CARRIER', 'SEUR'),

    'carriers' => [
        'SEUR' => [
            'endpoint' => env('SEUR_API_ENDPOINT', 'https://api.seur.com/v1'),
            'tracking_endpoint' => env('SEUR_TRACKING_ENDPOINT', 'https://api.seur.com/tracking/v1'),
            'key' => env('SEUR_API_KEY'),
            'secret' => env('SEUR_API_SECRET'),
            'test_mode' => env('SEUR_TEST_MODE', false),
        ],

        'CORREOS' => [
            'endpoint' => env('CORREOS_API_ENDPOINT', 'https://api.correos.es/v2'),
            'auth_endpoint' => env('CORREOS_AUTH_ENDPOINT', 'https://api.correos.es/auth'),
            'tracking_endpoint' => env('CORREOS_TRACKING_ENDPOINT', 'https://www.correos.es/api/tracking'),
            'username' => env('CORREOS_USERNAME'),
            'password' => env('CORREOS_PASSWORD'),
            'contract_code' => env('CORREOS_CONTRACT_CODE'),
        ],

        'INPOST' => [
            'endpoint' => env('INPOST_API_ENDPOINT', 'https://api.inpost.es/v1'),
            'token' => env('INPOST_API_TOKEN'),
            'test_mode' => env('INPOST_TEST_MODE', false),
        ]
    ],

    'pickup' => [
        'advance_days' => 1, // Días mínimos de antelación para programar recogida
        'max_days' => 30,    // Días máximos para programar recogida
        'default_packages' => 1,
        'default_weight' => 1.0, // kg
    ],

    'labels' => [
        'format' => 'PDF',
        'size' => 'A4',
        'dpi' => 300,
    ]
];
