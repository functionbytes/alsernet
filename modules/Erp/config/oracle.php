<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Oracle Database Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all Oracle database connection settings for the ERP module.
    | These settings are stored in the database (settings table) and can be
    | managed through the admin panel at /settings/erp/database
    |
    */

    'connection' => [
        'driver' => 'oracle',
        // Connection details are loaded from env, then overridden at connection
        // time by ErpServiceProvider::applyDynamicOracleConfig() with values
        // from the Settings table. Never commit credentials here.
        'host' => env('ORACLE_HOST'),
        'hostname' => env('ORACLE_HOST'), // yajra/laravel-oci8 requires 'hostname'
        'port' => (int) env('ORACLE_PORT', 1521),
        'database' => env('ORACLE_DATABASE'),
        'service_name' => env('ORACLE_SERVICE_NAME'),
        'username' => env('ORACLE_USERNAME'),
        'password' => env('ORACLE_PASSWORD'),
        'charset' => env('ORACLE_CHARSET', 'AL32UTF8'),
        'prefix' => '',
        'prefix_schema' => env('ORACLE_SCHEMA'),
        'server_version' => env('ORACLE_SERVER_VERSION', '11g'),
        'load_balance' => env('ORACLE_LOAD_BALANCE', 'yes'),
        'pooled' => true,
        'options' => [
            // false: evita que conexiones muertas (ORA-03113) contaminen requests posteriores del mismo worker PHP-FPM
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Connection Settings
    |--------------------------------------------------------------------------
    */

    'timeout' => 30, // Connection timeout in seconds
    'retry_attempts' => 3, // Number of retry attempts on connection failure

];
