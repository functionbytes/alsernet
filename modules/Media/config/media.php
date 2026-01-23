<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Media Manager Configuration
    |--------------------------------------------------------------------------
    */

    'default_disk' => env('MEDIA_DEFAULT_DISK', 'media'),

    'max_upload_size' => env('MEDIA_MAX_UPLOAD_SIZE', 104857600), // 100MB

    'allowed_mime_types' => [
        'image/*',
        'video/*',
        'audio/*',
        'application/pdf',
        'application/zip',
    ],

    'image_optimization' => [
        'enabled' => env('MEDIA_IMAGE_OPTIMIZATION', true),
        'quality' => env('MEDIA_IMAGE_QUALITY', 85),
    ],
];
