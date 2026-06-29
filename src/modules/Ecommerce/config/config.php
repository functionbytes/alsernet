<?php

return [
    'name' => 'Ecommerce',
    'currency' => env('ECOMMERCE_CURRENCY', 'USD'),
    'currency_symbol' => env('ECOMMERCE_CURRENCY_SYMBOL', '$'),
    'products_per_page' => 12,
    'allow_guest_checkout' => true,
    'default_status' => 'published',
    'slug_separator' => '-',
    'low_stock_threshold' => 5,
];
