<?php

return [

    'default_disk' => env('MEDIA_DEFAULT_DISK', 'media'),

    'max_upload_size' => env('MEDIA_MAX_UPLOAD_SIZE', 104857600), // 100MB in bytes

    'allowed_mime_types' => env(
        'MEDIA_ALLOWED_MIME_TYPES',
        'jpg,jpeg,png,gif,webp,avif,bmp,svg,ico,mp4,m4v,mov,mp3,wav,pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,txt,csv'
    ),

    'mime_types' => [
        'image' => [
            'image/png',
            'image/jpeg',
            'image/gif',
            'image/bmp',
            'image/svg+xml',
            'image/webp',
            'image/avif',
            'image/x-icon',
            'image/vnd.microsoft.icon',
        ],
        'video' => [
            'video/mp4',
            'video/m4v',
            'video/mov',
            'video/quicktime',
            'video/webm',
        ],
        'audio' => [
            'audio/mpeg',
            'audio/mp3',
            'audio/wav',
            'audio/webm',
        ],
        'document' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'text/csv',
        ],
        'zip' => [
            'application/zip',
            'application/x-zip-compressed',
            'application/x-rar-compressed',
            'application/x-compressed',
        ],
    ],

    'sizes' => [
        'thumb' => '150x150',
        'medium' => '540x360',
    ],

    'image_optimization' => [
        'enabled' => env('MEDIA_IMAGE_OPTIMIZATION', true),
        'quality' => env('MEDIA_IMAGE_QUALITY', 85),
    ],

    'generate_thumbnails_enabled' => env('MEDIA_GENERATE_THUMBNAILS', true),

    'watermark' => [
        'enabled' => env('MEDIA_WATERMARK_ENABLED', false),
        'source' => env('MEDIA_WATERMARK_SOURCE'),
        'size' => env('MEDIA_WATERMARK_SIZE', 10),
        'opacity' => env('MEDIA_WATERMARK_OPACITY', 70),
        'position' => env('MEDIA_WATERMARK_POSITION', 'bottom-right'),
    ],

    'chunk' => [
        'enabled' => env('MEDIA_CHUNK_UPLOAD', false),
        'chunk_size' => 1024 * 1024, // 1MB
        'max_file_size' => 1024 * 100, // 100MB
        'storage' => [
            'chunks' => 'chunks',
            'disk' => 'local',
        ],
        'clear' => [
            'timestamp' => '-3 HOURS',
            'schedule' => [
                'enabled' => true,
                'cron' => '25 * * * *',
            ],
        ],
    ],

    'folder_colors' => [
        '#3498db',
        '#2ecc71',
        '#e74c3c',
        '#f39c12',
        '#9b59b6',
        '#1abc9c',
        '#34495e',
        '#e67e22',
        '#27ae60',
        '#c0392b',
    ],

    'sidebar_display' => env('MEDIA_SIDEBAR_DISPLAY', 'horizontal'),

    'default_image' => env('MEDIA_DEFAULT_IMAGE', null),

];
