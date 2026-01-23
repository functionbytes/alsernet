<?php

return [
    'name' => 'Subscriber',
    'description' => 'Subscriber module for managing email lists and subscribers',

    'import' => [
        'batch_size' => 1000,
        'encoding' => 'UTF-8',
    ],

    'verification' => [
        'enabled' => true,
        'timeout' => 120,
        'retry_hours' => 12,
    ],

    'export' => [
        'format' => 'csv',
        'delimiter' => ',',
    ],
];
