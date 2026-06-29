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

    'convert_to_webp' => env('MEDIA_CONVERT_TO_WEBP', true),
    'convert_to_avif' => env('MEDIA_CONVERT_TO_AVIF', true),
    'avif_quality' => env('MEDIA_AVIF_QUALITY', 60),

    'cdn' => [
        'enabled' => env('MEDIA_CDN_ENABLED', false),
        'url' => env('MEDIA_CDN_URL', ''),
        'signed_urls' => env('MEDIA_CDN_SIGNED_URLS', false),
        'signature_key' => env('MEDIA_CDN_SIGNATURE_KEY', ''),
        'signature_expiration' => env('MEDIA_CDN_SIGNATURE_EXPIRATION', 3600),
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

    'quota' => [
        'enabled' => env('MEDIA_QUOTA_ENABLED', false),
        'default_bytes' => env('MEDIA_QUOTA_DEFAULT_BYTES', 1073741824), // 1GB
    ],

    'virus_scan' => [
        'enabled' => env('MEDIA_VIRUS_SCAN_ENABLED', false),
        'clamscan_path' => env('MEDIA_CLAMSCAN_PATH', 'clamscan'),
    ],

    'ai' => [
        'auto_tagging' => [
            'driver' => env('MEDIA_AUTOTAG_DRIVER', 'null'), // null, .claude, openai, google
            'api_key' => env('MEDIA_AUTOTAG_API_KEY'),
        ],
        'pii_detection' => [
            'driver' => env('MEDIA_PII_DRIVER', 'null'), // null, .claude
            'api_key' => env('MEDIA_PII_API_KEY'),
        ],
        'auto_caption' => [
            'driver' => env('MEDIA_CAPTION_DRIVER', 'null'), // null, .claude, openai
            'api_key' => env('MEDIA_CAPTION_API_KEY'),
        ],
        'transcription' => [
            'driver' => env('MEDIA_TRANSCRIPTION_DRIVER', 'null'), // null, whisper
            'api_key' => env('MEDIA_TRANSCRIPTION_API_KEY'),
        ],
    ],

    'ffmpeg' => [
        'path' => env('MEDIA_FFMPEG_PATH', 'ffmpeg'),
        'ffprobe_path' => env('MEDIA_FFPROBE_PATH', 'ffprobe'),
    ],

    'tesseract' => [
        'path' => env('MEDIA_TESSERACT_PATH', 'tesseract'),
        'languages' => env('MEDIA_TESSERACT_LANGS', 'spa+eng'),
    ],

    'logging' => [
        'channel' => env('MEDIA_LOG_CHANNEL', 'stack'),
        'structured' => env('MEDIA_LOG_STRUCTURED', false),
    ],

    'retention' => [
        'enabled' => env('MEDIA_RETENTION_ENABLED', false),
        'policies' => [
            'pdf' => env('MEDIA_RETENTION_PDF', '7 years'),
            'document' => env('MEDIA_RETENTION_DOCUMENT', '5 years'),
            'image' => null,
            'video' => env('MEDIA_RETENTION_VIDEO', '2 years'),
            'audio' => env('MEDIA_RETENTION_AUDIO', '2 years'),
            'zip' => env('MEDIA_RETENTION_ZIP', '1 year'),
        ],
    ],

    'user_watermark' => [
        'enabled' => env('MEDIA_USER_WATERMARK', false),
        'font_size' => env('MEDIA_USER_WATERMARK_SIZE', 12),
        'required_for_visibility' => ['private'],
    ],

    'cloud_import' => [
        'dropbox' => [
            'client_id' => env('MEDIA_DROPBOX_CLIENT_ID'),
            'client_secret' => env('MEDIA_DROPBOX_CLIENT_SECRET'),
            'redirect_uri' => env('MEDIA_DROPBOX_REDIRECT', '/panel/media/cloud/dropbox/callback'),
        ],
        'gdrive' => [
            'client_id' => env('MEDIA_GDRIVE_CLIENT_ID'),
            'client_secret' => env('MEDIA_GDRIVE_CLIENT_SECRET'),
            'redirect_uri' => env('MEDIA_GDRIVE_REDIRECT', '/panel/media/cloud/gdrive/callback'),
        ],
        'onedrive' => [
            'client_id' => env('MEDIA_ONEDRIVE_CLIENT_ID'),
            'client_secret' => env('MEDIA_ONEDRIVE_CLIENT_SECRET'),
            'redirect_uri' => env('MEDIA_ONEDRIVE_REDIRECT', '/panel/media/cloud/onedrive/callback'),
        ],
    ],

];
