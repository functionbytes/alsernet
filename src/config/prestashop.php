<?php

return [
    // Database credentials - use existing config keys from Prestashop module
    'host' => env('DB_HOST_PRESTASHOP', config('prestashop.db_host', 'localhost')),
    'port' => env('DB_PORT_PRESTASHOP', config('prestashop.db_port', 3306)),
    'database' => env('DB_DATABASE_PRESTASHOP', config('prestashop.db_database', 'prestashop')),
    'username' => env('DB_USERNAME_PRESTASHOP', config('prestashop.db_username', 'root')),
    'password' => env('DB_PASSWORD_PRESTASHOP', config('prestashop.db_password', '')),
];
