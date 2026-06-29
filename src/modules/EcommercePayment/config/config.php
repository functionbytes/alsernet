<?php

return [
    'name' => 'EcommercePayment',
    'default_currency' => env('ECOMMERCE_PAYMENT_CURRENCY', 'USD'),
    'wompi' => [
        'enabled' => false,
        'mode' => 'sandbox',
        'public_key' => env('WOMPI_PUBLIC_KEY', ''),
        'private_key' => env('WOMPI_PRIVATE_KEY', ''),
        'integrity_secret' => env('WOMPI_INTEGRITY_SECRET', ''),
        'event_secret' => env('WOMPI_EVENT_SECRET', ''),
        'exchange_rate' => [
            'USD' => 4000,
            'EUR' => 4300,
        ],
    ],
];
