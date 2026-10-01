<?php

/*
 | Se fusiona bajo la clave `helpdeskmedia` (NO `media`: esa ya es la del
 | módulo Media y se pisarían). Acceso: config('helpdeskmedia.images.quality').
 */
return [
    'name' => 'HelpdeskMedia',

    // Interruptor general. Apagado: no se encola nada.
    'enabled' => (bool) env('HELPDESK_MEDIA_ENABLED', true),

    // Horizon (supervisor-media) atiende media-light/optimize/scan; una cola
    // llamada solo "media" no la consumiría nadie.
    'queue' => env('HELPDESK_MEDIA_QUEUE', 'media-optimize'),
    'tries' => 3,
    'backoff' => [30, 120, 300],
    'timeout' => (int) env('HELPDESK_MEDIA_JOB_TIMEOUT', 300),

    // Orígenes que se procesan.
    'sources' => [
        'conversations' => (bool) env('HELPDESK_MEDIA_CONVERSATIONS', true),
        'tickets' => (bool) env('HELPDESK_MEDIA_TICKETS', true),
    ],

    // clamd por TCP (protocolo INSTREAM), sin paquetes ni binarios locales.
    'clamav' => [
        'enabled' => (bool) env('CLAMAV_ENABLED', false),
        'host' => env('CLAMAV_HOST', '127.0.0.1'),
        'port' => (int) env('CLAMAV_PORT', 3310),
        'timeout' => (int) env('CLAMAV_TIMEOUT', 30),
        'connect_timeout' => (int) env('CLAMAV_CONNECT_TIMEOUT', 5),
        'chunk_bytes' => 65536,
        // Por encima de esto no se escanea (clamd corta en StreamMaxLength, 25 MB por defecto).
        'max_mb' => (int) env('CLAMAV_MAX_MB', 25),
    ],

    // false: si clamd no responde el adjunto sigue su curso marcado "unavailable".
    // true: se pone en cuarentena hasta poder escanearlo.
    'fail_closed' => (bool) env('HELPDESK_MEDIA_FAIL_CLOSED', false),

    // Disco NO público. Aquí van los infectados y, si keep_original, los originales.
    'quarantine' => [
        'disk' => env('HELPDESK_MEDIA_QUARANTINE_DISK', 'local'),
        'path' => 'helpdesk-media/quarantine',
    ],

    // Conserva el original (sin optimizar, con EXIF) en el disco privado.
    'keep_original' => (bool) env('HELPDESK_MEDIA_KEEP_ORIGINAL', false),
    'originals_path' => 'helpdesk-media/originals',

    'images' => [
        'enabled' => (bool) env('HELPDESK_MEDIA_IMAGES', true),
        'driver' => env('HELPDESK_MEDIA_IMAGE_DRIVER', 'auto'), // auto | imagick | gd
        'quality' => (int) env('HELPDESK_MEDIA_IMAGE_QUALITY', 82),
        'max_px' => (int) env('HELPDESK_MEDIA_IMAGE_MAX_PX', 2048),
        'max_mb' => (int) env('HELPDESK_MEDIA_IMAGE_MAX_MB', 25),
        // Anti pixel-bomb: más píxeles que esto no se decodifica.
        'max_pixels' => (int) env('HELPDESK_MEDIA_IMAGE_MAX_PIXELS', 50000000),
    ],

    'audio' => [
        'enabled' => (bool) env('HELPDESK_MEDIA_AUDIO', true),
        'max_mb' => (int) env('HELPDESK_MEDIA_AUDIO_MAX_MB', 25),
    ],

    'transcription' => [
        'enabled' => (bool) env('HELPDESK_MEDIA_TRANSCRIPTION', true),
        'model' => env('HELPDESK_MEDIA_TRANSCRIPTION_MODEL', 'gpt-4o-mini-transcribe'),
        'fallback_model' => env('HELPDESK_MEDIA_TRANSCRIPTION_FALLBACK', 'whisper-1'),
        'language' => env('HELPDESK_MEDIA_TRANSCRIPTION_LANGUAGE'), // null = autodetección
        'timeout' => (int) env('HELPDESK_MEDIA_TRANSCRIPTION_TIMEOUT', 120),
        'ffmpeg_path' => env('FFMPEG_PATH', 'ffmpeg'),
    ],
];
