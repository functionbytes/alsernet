<?php

return [
    'skip' => [
        // Settings routes
        'setting/*', 'setting*',
        // Documents & data
        '*.xml', '*.pdf', '*.txt', '*.csv', '*.json', '*.ico', '*.rss',
        // Archives & executables
        '*.zip', '*.rar', '*.exe', '*.dmg', '*.iso', '*.torrent', '*.dat',
        // Media — audio
        '*.mp3', '*.wav', '*.m4a', '*.ogg', '*.flac',
        // Media — video
        '*.mp4', '*.m4v', '*.wmv', '*.avi', '*.mpg', '*.mpeg', '*.mov', '*.flv', '*.swf',
        // Media — image
        '*.jpg', '*.jpeg', '*.png', '*.gif', '*.webp', '*.svg', '*.tif', '*.tiff',
        // Design files
        '*.psd', '*.ai', '*.xls', '*.ppt',
        // Web assets (already handled by Content-Type check, but explicit is safer)
        '*.css', '*.js', '*.woff', '*.woff2', '*.ttf', '*.eot', '*.otf', '*.map',
    ],
];
