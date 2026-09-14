<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Storage Module Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Storage module that manages file storage disks
    |
    */

    'name' => 'Storage',

    'description' => 'Storage configuration and management module',

    'enabled' => true,

    // Supported storage drivers
    'drivers' => [
        'local' => 'Almacenamiento Local',
        'ftp' => 'FTP',
        'sftp' => 'SFTP',
        's3' => 'Amazon S3',
    ],

    // Core system disks (read-only)
    'core_disks' => [
        'local',
        'public',
    ],

    // Default storage disk
    'default_disk' => 'local',

    // Storage limits and restrictions
    'limits' => [
        'max_file_size' => 102400, // MB
        'max_disk_size' => 1000000, // MB
    ],
];
