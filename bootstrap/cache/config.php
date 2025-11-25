<?php return array (
  'concurrency' => 
  array (
    'default' => 'process',
  ),
  'app' => 
  array (
    'name' => 'A-alvarez',
    'env' => 'local',
    'debug' => true,
    'url' => 'https://webadminpruebas.a-alvarez.com',
    'frontend_url' => 'http://localhost:3000',
    'asset_url' => NULL,
    'timezone' => 'UTC',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'faker_locale' => 'en_US',
    'cipher' => 'AES-256-CBC',
    'key' => 'base64:uw9ildLKily5v0ZHlv0d84JMx/VpqR/kPIf9tCh+ZKA=',
    'previous_keys' => 
    array (
    ),
    'maintenance' => 
    array (
      'driver' => 'file',
      'store' => 'database',
    ),
    'providers' => 
    array (
      0 => 'BotMan\\BotMan\\BotManServiceProvider',
      1 => 'Illuminate\\Auth\\AuthServiceProvider',
      2 => 'Illuminate\\Broadcasting\\BroadcastServiceProvider',
      3 => 'Illuminate\\Bus\\BusServiceProvider',
      4 => 'Illuminate\\Cache\\CacheServiceProvider',
      5 => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
      6 => 'Illuminate\\Cookie\\CookieServiceProvider',
      7 => 'Illuminate\\Database\\DatabaseServiceProvider',
      8 => 'Illuminate\\Encryption\\EncryptionServiceProvider',
      9 => 'Illuminate\\Filesystem\\FilesystemServiceProvider',
      10 => 'Illuminate\\Foundation\\Providers\\FoundationServiceProvider',
      11 => 'Illuminate\\Hashing\\HashServiceProvider',
      12 => 'Illuminate\\Mail\\MailServiceProvider',
      13 => 'Illuminate\\Notifications\\NotificationServiceProvider',
      14 => 'Illuminate\\Pagination\\PaginationServiceProvider',
      15 => 'Illuminate\\Pipeline\\PipelineServiceProvider',
      16 => 'Illuminate\\Queue\\QueueServiceProvider',
      17 => 'Illuminate\\Redis\\RedisServiceProvider',
      18 => 'Illuminate\\Auth\\Passwords\\PasswordResetServiceProvider',
      19 => 'Illuminate\\Session\\SessionServiceProvider',
      20 => 'Illuminate\\Translation\\TranslationServiceProvider',
      21 => 'Illuminate\\Validation\\ValidationServiceProvider',
      22 => 'Illuminate\\View\\ViewServiceProvider',
      23 => 'Artesaos\\SEOTools\\Providers\\SEOToolsServiceProvider',
      24 => 'Maatwebsite\\Excel\\ExcelServiceProvider',
      25 => 'App\\Providers\\AppServiceProvider',
      26 => 'App\\Providers\\EventServiceProvider',
      27 => 'App\\Providers\\RouteServiceProvider',
      28 => 'App\\Providers\\JobServiceProvider',
      29 => 'Illuminate\\Cache\\CacheServiceProvider',
      30 => 'Illuminate\\Database\\DatabaseServiceProvider',
      31 => 'Illuminate\\Filesystem\\FilesystemServiceProvider',
      32 => 'Illuminate\\View\\ViewServiceProvider',
      33 => 'Illuminate\\Pagination\\PaginationServiceProvider',
      34 => 'Illuminate\\Translation\\TranslationServiceProvider',
      35 => 'Illuminate\\Validation\\ValidationServiceProvider',
      36 => 'Illuminate\\Session\\SessionServiceProvider',
      37 => 'Illuminate\\Hashing\\HashServiceProvider',
      38 => 'Illuminate\\Bus\\BusServiceProvider',
      39 => 'Illuminate\\Queue\\QueueServiceProvider',
      40 => 'Illuminate\\Auth\\Passwords\\PasswordResetServiceProvider',
      41 => 'Illuminate\\Notifications\\NotificationServiceProvider',
      42 => 'App\\Providers\\AppServiceProvider',
      43 => 'App\\Providers\\EventServiceProvider',
      44 => 'App\\Providers\\RouteServiceProvider',
      45 => 'Illuminate\\Foundation\\Providers\\FoundationServiceProvider',
      46 => 'Illuminate\\Encryption\\EncryptionServiceProvider',
      47 => 'Illuminate\\Cookie\\CookieServiceProvider',
      48 => 'Illuminate\\Auth\\AuthServiceProvider',
      49 => 'Illuminate\\Redis\\RedisServiceProvider',
      50 => 'BotMan\\BotMan\\BotManServiceProvider',
      51 => 'App\\Providers\\AppServiceProvider',
    ),
    'aliases' => 
    array (
      'SEOMeta' => 'Artesaos\\SEOTools\\Facades\\SEOMeta',
      'OpenGraph' => 'Artesaos\\SEOTools\\Facades\\OpenGraph',
      'Twitter' => 'Artesaos\\SEOTools\\Facades\\TwitterCard',
      'JsonLd' => 'Artesaos\\SEOTools\\Facades\\JsonLd',
      'JsonLdMulti' => 'Artesaos\\SEOTools\\Facades\\JsonLdMulti',
      'SEO' => 'Artesaos\\SEOTools\\Facades\\SEOTools',
      'Str' => 'Illuminate\\Support\\Str',
      'Auth' => 'Illuminate\\Support\\Facades\\Auth',
      'Redis' => 'Illuminate\\Support\\Facades\\Redis',
      'Route' => 'Illuminate\\Support\\Facades\\Route',
      'Excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
    ),
  ),
  'auth' => 
  array (
    'defaults' => 
    array (
      'guard' => 'web',
      'passwords' => 'users',
    ),
    'guards' => 
    array (
      'web' => 
      array (
        'driver' => 'session',
        'provider' => 'users',
      ),
      'sanctum' => 
      array (
        'driver' => 'sanctum',
        'provider' => NULL,
      ),
    ),
    'providers' => 
    array (
      'users' => 
      array (
        'driver' => 'eloquent',
        'model' => 'App\\Models\\User',
      ),
    ),
    'passwords' => 
    array (
      'users' => 
      array (
        'provider' => 'users',
        'table' => 'password_reset_tokens',
        'expire' => 60,
        'throttle' => 60,
      ),
    ),
    'password_timeout' => 10800,
  ),
  'barcode' => 
  array (
    'store_path' => '/home2/webadminpruebas/web/public/',
  ),
  'broadcasting' => 
  array (
    'default' => 'null',
    'connections' => 
    array (
      'reverb' => 
      array (
        'driver' => 'reverb',
        'key' => NULL,
        'secret' => NULL,
        'app_id' => NULL,
        'options' => 
        array (
          'host' => NULL,
          'port' => 443,
          'scheme' => 'https',
          'useTLS' => true,
        ),
        'client_options' => 
        array (
        ),
      ),
      'pusher' => 
      array (
        'driver' => 'pusher',
        'key' => NULL,
        'secret' => NULL,
        'app_id' => NULL,
        'options' => 
        array (
          'cluster' => NULL,
          'host' => 'webadminpruebas.a-alvarez.com',
          'port' => 6001,
          'scheme' => 'https',
          'encrypted' => true,
          'useTLS' => true,
        ),
        'client_options' => 
        array (
        ),
      ),
      'ably' => 
      array (
        'driver' => 'ably',
        'key' => NULL,
      ),
      'log' => 
      array (
        'driver' => 'log',
      ),
      'null' => 
      array (
        'driver' => 'null',
      ),
      'redis' => 
      array (
        'driver' => 'redis',
        'connection' => 'default',
      ),
    ),
  ),
  'cache' => 
  array (
    'default' => 'file',
    'stores' => 
    array (
      'array' => 
      array (
        'driver' => 'array',
        'serialize' => false,
      ),
      'database' => 
      array (
        'driver' => 'database',
        'table' => 'cache',
        'connection' => NULL,
        'lock_connection' => NULL,
      ),
      'file' => 
      array (
        'driver' => 'file',
        'path' => '/home2/webadminpruebas/web/storage/framework/cache/data',
        'lock_path' => '/home2/webadminpruebas/web/storage/framework/cache/data',
      ),
      'memcached' => 
      array (
        'driver' => 'memcached',
        'persistent_id' => NULL,
        'sasl' => 
        array (
          0 => NULL,
          1 => NULL,
        ),
        'options' => 
        array (
        ),
        'servers' => 
        array (
          0 => 
          array (
            'host' => '127.0.0.1',
            'port' => 11211,
            'weight' => 100,
          ),
        ),
      ),
      'redis' => 
      array (
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
      ),
      'dynamodb' => 
      array (
        'driver' => 'dynamodb',
        'key' => '',
        'secret' => '',
        'region' => 'us-east-1',
        'table' => 'cache',
        'endpoint' => NULL,
      ),
      'octane' => 
      array (
        'driver' => 'octane',
      ),
      'apc' => 
      array (
        'driver' => 'apc',
      ),
    ),
    'prefix' => '',
  ),
  'cors' => 
  array (
    'paths' => 
    array (
      0 => 'api/*',
      1 => 'sanctum/csrf-cookie',
    ),
    'allowed_methods' => 
    array (
      0 => '*',
    ),
    'allowed_origins' => 
    array (
      0 => '*',
    ),
    'allowed_origins_patterns' => 
    array (
    ),
    'allowed_headers' => 
    array (
      0 => '*',
    ),
    'exposed_headers' => 
    array (
    ),
    'max_age' => 0,
    'supports_credentials' => false,
  ),
  'custom' => 
  array (
    'date_format' => 'Y-m-d',
    'date_format_sql' => '%Y-%m-%d',
    'time_format' => 'H:i',
    'php_recommended' => '7.3.0',
    'php' => '7.3.0',
    'default_logo_light' => 'images/logo_light.svg',
    'default_logo_dark' => 'images/logo_dark.svg',
    'woo' => false,
    'japan' => false,
    'app_profile' => NULL,
    'dryrun' => false,
    'license_verification_endpoint' => 'http://verify.acellemail.com',
  ),
  'database' => 
  array (
    'default' => 'mysql',
    'connections' => 
    array (
      'sqlite' => 
      array (
        'driver' => 'sqlite',
        'url' => NULL,
        'database' => 'webadminpruebas',
        'prefix' => '',
        'foreign_key_constraints' => true,
        'busy_timeout' => NULL,
        'journal_mode' => NULL,
        'synchronous' => NULL,
      ),
      'mysql' => 
      array (
        'driver' => 'mysql',
        'url' => NULL,
        'host' => '192.168.1.120',
        'port' => '3306',
        'database' => 'webadminpruebas',
        'username' => 'webadmin',
        'password' => 'Mar.90272618',
        'unix_socket' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => NULL,
        'options' => 
        array (
        ),
      ),
      'mariadb' => 
      array (
        'driver' => 'mariadb',
        'url' => NULL,
        'host' => '192.168.1.120',
        'port' => '3306',
        'database' => 'webadminpruebas',
        'username' => 'webadmin',
        'password' => 'Mar.90272618',
        'unix_socket' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => NULL,
        'options' => 
        array (
        ),
      ),
      'pgsql' => 
      array (
        'driver' => 'pgsql',
        'url' => NULL,
        'host' => '192.168.1.120',
        'port' => '3306',
        'database' => 'webadminpruebas',
        'username' => 'webadmin',
        'password' => 'Mar.90272618',
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
        'search_path' => 'public',
        'sslmode' => 'prefer',
      ),
      'sqlsrv' => 
      array (
        'driver' => 'sqlsrv',
        'url' => NULL,
        'host' => '192.168.1.120',
        'port' => '3306',
        'database' => 'webadminpruebas',
        'username' => 'webadmin',
        'password' => 'Mar.90272618',
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
      ),
      'prestashop' => 
      array (
        'driver' => 'mysql',
        'host' => '192.168.1.120',
        'port' => 3306,
        'database' => 'alvarez_ana',
        'username' => 'alvarez_ana',
        'password' => 'Jun.007862',
      ),
      'prestashop12' => 
      array (
        'driver' => 'mysql',
        'host' => '192.168.1.120',
        'port' => 3306,
        'database' => 'alvarez_cristia',
        'username' => 'alvarez_cristia',
        'password' => 'Abr.755441',
      ),
      'prestashops' => 
      array (
        'driver' => 'mysql',
        'host' => '192.168.1.120',
        'port' => '3306',
        'database' => 'alvarez_ana',
        'username' => 'alvarez_ana',
        'password' => 'Jun.007862',
        'unix_socket' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
        'engine' => NULL,
      ),
    ),
    'migrations' => 
    array (
      'table' => 'migrations',
      'update_date_on_publish' => true,
    ),
    'redis' => 
    array (
      'client' => 'phpredis',
      'options' => 
      array (
        'cluster' => 'redis',
        'prefix' => 'a_alvarez_database_',
      ),
      'default' => 
      array (
        'url' => NULL,
        'host' => '127.0.0.1',
        'username' => NULL,
        'password' => NULL,
        'port' => '6379',
        'database' => '0',
      ),
      'cache' => 
      array (
        'url' => NULL,
        'host' => '127.0.0.1',
        'username' => NULL,
        'password' => NULL,
        'port' => '6379',
        'database' => '1',
      ),
    ),
  ),
  'documents' => 
  array (
    'upload_portal_url' => 'Mar.76100856',
    'paid_statuses' => 
    array (
      0 => 0,
    ),
  ),
  'erp' => 
  array (
    'url_erp' => 'http://192.168.1.3:58002',
    'bono_origen_web' => 'web',
    'bono_origen_gestion' => 'gestion',
    'marcar_bono_anular' => 0,
    'marcar_bono_recargar' => 1,
    'marcar_bono_consumir' => 2,
    'payment_cashondelivery' => 1,
    'payment_wire' => 3,
    'payment_creditcard' => 7,
    'payment_redsys' => 22,
    'payment_bizum' => 8,
    'payment_google' => 26,
    'payment_apple' => 27,
    'payment_paypal' => 10,
    'payment_finance' => 11,
    'payment_sequra' => 100000101,
    'payment_alsernetfinance' => 5,
    'payment_transferencia_online' => '25',
    'payment_ban_lendismart' => 28,
    'payment_bizum_tpv' => 2,
    'payment_google_tpv' => 3,
    'payment_apple_tpv' => 2,
  ),
  'excel' => 
  array (
    'exports' => 
    array (
      'chunk_size' => 1000,
      'pre_calculate_formulas' => false,
      'strict_null_comparison' => false,
      'csv' => 
      array (
        'delimiter' => ',',
        'enclosure' => '"',
        'line_ending' => '
',
        'use_bom' => false,
        'include_separator_line' => false,
        'excel_compatibility' => false,
        'output_encoding' => '',
        'test_auto_detect' => true,
      ),
      'properties' => 
      array (
        'creator' => '',
        'lastModifiedBy' => '',
        'title' => '',
        'description' => '',
        'subject' => '',
        'keywords' => '',
        'category' => '',
        'manager' => '',
        'company' => '',
      ),
    ),
    'imports' => 
    array (
      'read_only' => true,
      'ignore_empty' => false,
      'heading_row' => 
      array (
        'formatter' => 'slug',
      ),
      'csv' => 
      array (
        'delimiter' => NULL,
        'enclosure' => '"',
        'escape_character' => '\\',
        'contiguous' => false,
        'input_encoding' => 'guess',
      ),
      'properties' => 
      array (
        'creator' => '',
        'lastModifiedBy' => '',
        'title' => '',
        'description' => '',
        'subject' => '',
        'keywords' => '',
        'category' => '',
        'manager' => '',
        'company' => '',
      ),
      'cells' => 
      array (
        'middleware' => 
        array (
        ),
      ),
    ),
    'extension_detector' => 
    array (
      'xlsx' => 'Xlsx',
      'xlsm' => 'Xlsx',
      'xltx' => 'Xlsx',
      'xltm' => 'Xlsx',
      'xls' => 'Xls',
      'xlt' => 'Xls',
      'ods' => 'Ods',
      'ots' => 'Ods',
      'slk' => 'Slk',
      'xml' => 'Xml',
      'gnumeric' => 'Gnumeric',
      'htm' => 'Html',
      'html' => 'Html',
      'csv' => 'Csv',
      'tsv' => 'Csv',
      'pdf' => 'Dompdf',
    ),
    'value_binder' => 
    array (
      'default' => 'Maatwebsite\\Excel\\DefaultValueBinder',
    ),
    'cache' => 
    array (
      'driver' => 'memory',
      'batch' => 
      array (
        'memory_limit' => 60000,
      ),
      'illuminate' => 
      array (
        'store' => NULL,
      ),
      'default_ttl' => 10800,
    ),
    'transactions' => 
    array (
      'handler' => 'db',
      'db' => 
      array (
        'connection' => NULL,
      ),
    ),
    'temporary_files' => 
    array (
      'local_path' => '/home2/webadminpruebas/web/storage/framework/cache/laravel-excel',
      'local_permissions' => 
      array (
      ),
      'remote_disk' => NULL,
      'remote_prefix' => NULL,
      'force_resync_remote' => NULL,
    ),
  ),
  'filesystems' => 
  array (
    'default' => 'local',
    'disks' => 
    array (
      'local' => 
      array (
        'driver' => 'local',
        'root' => '/home2/webadminpruebas/web/storage/app',
      ),
      'public' => 
      array (
        'driver' => 'local',
        'root' => '/home2/webadminpruebas/web/storage/app/public',
        'url' => 'https://webadminpruebas.a-alvarez.com/storage',
        'visibility' => 'public',
      ),
      's3' => 
      array (
        'driver' => 's3',
        'key' => '',
        'secret' => '',
        'region' => 'us-east-1',
        'bucket' => '',
        'url' => NULL,
        'endpoint' => NULL,
        'use_path_style_endpoint' => false,
      ),
      'media' => 
      array (
        'driver' => 'local',
        'root' => '/home2/webadminpruebas/web/public/media',
        'url' => 'https://webadminpruebas.a-alvarez.com/media',
      ),
      'ftp' => 
      array (
        'driver' => 'ftp',
        'host' => 'imagenes.a-alvarez.com',
        'username' => 'imagenes_alvarez',
        'password' => 'Ago.90081761',
        'port' => '21',
        'root' => '/mailers',
        'passive' => true,
        'ssl' => false,
        'timeout' => 30,
      ),
    ),
    'links' => 
    array (
      '/home2/webadminpruebas/web/public/storage' => '/home2/webadminpruebas/web/storage/app/public',
    ),
  ),
  'hashing' => 
  array (
    'driver' => 'bcrypt',
    'bcrypt' => 
    array (
      'rounds' => '12',
      'verify' => true,
    ),
    'argon' => 
    array (
      'memory' => 65536,
      'threads' => 1,
      'time' => 4,
      'verify' => true,
    ),
    'rehash_on_login' => true,
  ),
  'languages' => 
  array (
    'aa' => 'Afar',
    'ab' => 'Abkhaz',
    'ae' => 'Avestan',
    'af' => 'Afrikaans',
    'ak' => 'Akan',
    'am' => 'Amharic',
    'an' => 'Aragonese',
    'ar' => 'Arabic',
    'as' => 'Assamese',
    'av' => 'Avaric',
    'ay' => 'Aymara',
    'az' => 'Azerbaijani',
    'ba' => 'Bashkir',
    'be' => 'Belarusian',
    'bg' => 'Bulgarian',
    'bh' => 'Bihari',
    'bi' => 'Bislama',
    'bm' => 'Bambara',
    'bn' => 'Bengali',
    'bo' => 'Tibetan Standard, Tibetan, Central',
    'br' => 'Breton',
    'bs' => 'Bosnian',
    'ca' => 'Catalan; Valencian',
    'ce' => 'Chechen',
    'ch' => 'Chamorro',
    'co' => 'Corsican',
    'cr' => 'Cree',
    'cs' => 'Czech',
    'cu' => 'Old Church Slavonic, Church Slavic, Church Slavonic, Old Bulgarian, Old Slavonic',
    'cv' => 'Chuvash',
    'cy' => 'Welsh',
    'da' => 'Danish',
    'de' => 'German',
    'dv' => 'Divehi; Dhivehi; Maldivian;',
    'dz' => 'Dzongkha',
    'ee' => 'Ewe',
    'el' => 'Greek, Modern',
    'en' => 'English',
    'eo' => 'Esperanto',
    'es' => 'Spanish; Castilian',
    'et' => 'Estonian',
    'eu' => 'Basque',
    'fa' => 'Persian',
    'ff' => 'Fula; Fulah; Pulaar; Pular',
    'fi' => 'Finnish',
    'fj' => 'Fijian',
    'fo' => 'Faroese',
    'fr' => 'French',
    'fy' => 'Western Frisian',
    'ga' => 'Irish',
    'gd' => 'Scottish Gaelic; Gaelic',
    'gl' => 'Galician',
    'gn' => 'GuaranÃ­',
    'gu' => 'Gujarati',
    'gv' => 'Manx',
    'ha' => 'Hausa',
    'he' => 'Hebrew (modern)',
    'hi' => 'Hindi',
    'ho' => 'Hiri Motu',
    'hr' => 'Croatian',
    'ht' => 'Haitian; Haitian Creole',
    'hu' => 'Hungarian',
    'hy' => 'Armenian',
    'hz' => 'Herero',
    'ia' => 'Interlingua',
    'id' => 'Indonesian',
    'ie' => 'Interlingue',
    'ig' => 'Igbo',
    'ii' => 'Nuosu',
    'ik' => 'Inupiaq',
    'io' => 'Ido',
    'is' => 'Icelandic',
    'it' => 'Italian',
    'iu' => 'Inuktitut',
    'ja' => 'Japanese (ja)',
    'jv' => 'Javanese (jv)',
    'ka' => 'Georgian',
    'kg' => 'Kongo',
    'ki' => 'Kikuyu, Gikuyu',
    'kj' => 'Kwanyama, Kuanyama',
    'kk' => 'Kazakh',
    'kl' => 'Kalaallisut, Greenlandic',
    'km' => 'Khmer',
    'kn' => 'Kannada',
    'ko' => 'Korean',
    'kr' => 'Kanuri',
    'ks' => 'Kashmiri',
    'ku' => 'Kurdish',
    'kv' => 'Komi',
    'kw' => 'Cornish',
    'ky' => 'Kirghiz, Kyrgyz',
    'la' => 'Latin',
    'lb' => 'Luxembourgish, Letzeburgesch',
    'lg' => 'Luganda',
    'li' => 'Limburgish, Limburgan, Limburger',
    'ln' => 'Lingala',
    'lo' => 'Lao',
    'lt' => 'Lithuanian',
    'lu' => 'Luba-Katanga',
    'lv' => 'Latvian',
    'mg' => 'Malagasy',
    'mh' => 'Marshallese',
    'mi' => 'Maori',
    'mk' => 'Macedonian',
    'ml' => 'Malayalam',
    'mn' => 'Mongolian',
    'mr' => 'Marathi (Mara?hi)',
    'ms' => 'Malay',
    'mt' => 'Maltese',
    'my' => 'Burmese',
    'na' => 'Nauru',
    'nb' => 'Norwegian BokmÃ¥l',
    'nd' => 'North Ndebele',
    'ne' => 'Nepali',
    'ng' => 'Ndonga',
    'nl' => 'Dutch',
    'nn' => 'Norwegian Nynorsk',
    'no' => 'Norwegian',
    'nr' => 'South Ndebele',
    'nv' => 'Navajo, Navaho',
    'ny' => 'Chichewa; Chewa; Nyanja',
    'oc' => 'Occitan',
    'oj' => 'Ojibwe, Ojibwa',
    'om' => 'Oromo',
    'or' => 'Oriya',
    'os' => 'Ossetian, Ossetic',
    'pa' => 'Panjabi, Punjabi',
    'pi' => 'Pali',
    'pl' => 'Polish',
    'ps' => 'Pashto, Pushto',
    'pt' => 'Portuguese',
    'qu' => 'Quechua',
    'rm' => 'Romansh',
    'rn' => 'Kirundi',
    'ro' => 'Romanian, Moldavian, Moldovan',
    'ru' => 'Russian',
    'rw' => 'Kinyarwanda',
    'sa' => 'Sanskrit (Sa?sk?ta)',
    'sc' => 'Sardinian',
    'sd' => 'Sindhi',
    'se' => 'Northern Sami',
    'sg' => 'Sango',
    'si' => 'Sinhala, Sinhalese',
    'sk' => 'Slovak',
    'sl' => 'Slovene',
    'sm' => 'Samoan',
    'sn' => 'Shona',
    'so' => 'Somali',
    'sq' => 'Albanian',
    'sr' => 'Serbian',
    'ss' => 'Swati',
    'st' => 'Southern Sotho',
    'su' => 'Sundanese',
    'sv' => 'Swedish',
    'sw' => 'Swahili',
    'ta' => 'Tamil',
    'te' => 'Telugu',
    'tg' => 'Tajik',
    'th' => 'Thai',
    'ti' => 'Tigrinya',
    'tk' => 'Turkmen',
    'tl' => 'Tagalog',
    'tn' => 'Tswana',
    'to' => 'Tonga (Tonga Islands)',
    'tr' => 'Turkish',
    'ts' => 'Tsonga',
    'tt' => 'Tatar',
    'tw' => 'Twi',
    'ty' => 'Tahitian',
    'ug' => 'Uighur, Uyghur',
    'uk' => 'Ukrainian',
    'ur' => 'Urdu',
    'uz' => 'Uzbek',
    've' => 'Venda',
    'vi' => 'Vietnamese',
    'vo' => 'VolapÃ¼k',
    'wa' => 'Walloon',
    'wo' => 'Wolof',
    'xh' => 'Xhosa',
    'yi' => 'Yiddish',
    'yo' => 'Yoruba',
    'za' => 'Zhuang, Chuang',
    'zh' => 'Chinese',
    'zu' => 'Zulu',
  ),
  'laravel-share' => 
  array (
    'services' => 
    array (
      'facebook' => 
      array (
        'uri' => 'https://www.facebook.com/sharer/sharer.php?u=',
      ),
      'twitter' => 
      array (
        'uri' => 'https://twitter.com/intent/tweet',
        'text' => 'Default share text',
      ),
      'linkedin' => 
      array (
        'uri' => 'https://www.linkedin.com/sharing/share-offsite',
        'extra' => 
        array (
          'mini' => 'true',
        ),
      ),
      'whatsapp' => 
      array (
        'uri' => 'https://wa.me/?text=',
        'extra' => 
        array (
          'mini' => 'true',
        ),
      ),
      'pinterest' => 
      array (
        'uri' => 'https://pinterest.com/pin/create/button/?url=',
      ),
      'reddit' => 
      array (
        'uri' => 'https://www.reddit.com/submit',
        'text' => 'Default share text',
      ),
      'telegram' => 
      array (
        'uri' => 'https://telegram.me/share/url',
        'text' => 'Default share text',
      ),
    ),
    'fontAwesomeVersion' => 5,
  ),
  'localization' => 
  array (
    '*' => 
    array (
      'date_full' => 'Y-m-d',
      'date_short' => 'Y-m-d',
      'date_without_year' => 'M-j',
      'datetime_full' => 'Y-m-d H:i',
      'datetime_full_with_timezone' => 'Y-m-d H:i T',
      'datetime_short' => 'Y-m-d H:i',
      'time_only' => 'H:i',
      'number_precision' => '2',
      'number_decimal_separator' => '.',
      'number_thousands_separator' => ',',
      'show_last_name_first' => false,
    ),
    'ja' => 
    array (
      'date_full' => 'Y年m月d日',
      'date_short' => 'Y/m/d',
      'datetime_full' => 'Y年m月d日 H:i',
      'datetime_short' => 'Y/m/d H:i',
      'time_only' => 'H:i',
      'show_last_name_first' => true,
    ),
  ),
  'logging' => 
  array (
    'default' => 'stack',
    'deprecations' => 
    array (
      'channel' => NULL,
      'trace' => false,
    ),
    'channels' => 
    array (
      'stack' => 
      array (
        'driver' => 'stack',
        'channels' => 
        array (
          0 => 'single',
        ),
        'ignore_exceptions' => false,
      ),
      'single' => 
      array (
        'driver' => 'single',
        'path' => '/home2/webadminpruebas/web/storage/logs/laravel.log',
        'level' => 'debug',
      ),
      'daily' => 
      array (
        'driver' => 'daily',
        'path' => '/home2/webadminpruebas/web/storage/logs/laravel.log',
        'level' => 'debug',
        'days' => 14,
      ),
      'slack' => 
      array (
        'driver' => 'slack',
        'url' => NULL,
        'username' => 'Laravel Log',
        'emoji' => ':boom:',
        'level' => 'debug',
        'replace_placeholders' => true,
      ),
      'papertrail' => 
      array (
        'driver' => 'monolog',
        'level' => 'debug',
        'handler' => 'Monolog\\Handler\\SyslogUdpHandler',
        'handler_with' => 
        array (
          'host' => NULL,
          'port' => NULL,
          'connectionString' => 'tls://:',
        ),
        'processors' => 
        array (
          0 => 'Monolog\\Processor\\PsrLogMessageProcessor',
        ),
      ),
      'stderr' => 
      array (
        'driver' => 'monolog',
        'level' => 'debug',
        'handler' => 'Monolog\\Handler\\StreamHandler',
        'formatter' => NULL,
        'with' => 
        array (
          'stream' => 'php://stderr',
        ),
      ),
      'syslog' => 
      array (
        'driver' => 'syslog',
        'level' => 'debug',
      ),
      'errorlog' => 
      array (
        'driver' => 'errorlog',
        'level' => 'debug',
      ),
      'null' => 
      array (
        'driver' => 'monolog',
        'handler' => 'Monolog\\Handler\\NullHandler',
      ),
      'emergency' => 
      array (
        'path' => '/home2/webadminpruebas/web/storage/logs/laravel.log',
      ),
      'returns' => 
      array (
        'driver' => 'daily',
        'path' => '/home2/webadminpruebas/web/storage/logs/returns/returns.log',
        'level' => 'debug',
        'days' => 30,
        'formatter' => 'App\\Logging\\ReturnLogFormatter',
      ),
      'metrics' => 
      array (
        'driver' => 'daily',
        'path' => '/home2/webadminpruebas/web/storage/logs/metrics/business-metrics.log',
        'level' => 'info',
        'days' => 90,
        'formatter' => 'App\\Logging\\MetricsLogFormatter',
      ),
      'payments' => 
      array (
        'driver' => 'daily',
        'path' => '/home2/webadminpruebas/web/storage/logs/payments/payments.log',
        'level' => 'debug',
        'days' => 365,
        'formatter' => 'App\\Logging\\PaymentLogFormatter',
      ),
      'audit' => 
      array (
        'driver' => 'daily',
        'path' => '/home2/webadminpruebas/web/storage/logs/audit/audit.log',
        'level' => 'info',
        'days' => 365,
        'formatter' => 'App\\Logging\\AuditLogFormatter',
      ),
      'critical' => 
      array (
        'driver' => 'stack',
        'channels' => 
        array (
          0 => 'critical-file',
          1 => 'slack-critical',
        ),
      ),
      'critical-file' => 
      array (
        'driver' => 'daily',
        'path' => '/home2/webadminpruebas/web/storage/logs/critical/critical.log',
        'level' => 'critical',
        'days' => 90,
      ),
      'slack-critical' => 
      array (
        'driver' => 'slack',
        'url' => NULL,
        'username' => 'Laravel Log',
        'emoji' => ':boom:',
        'level' => 'critical',
      ),
      'performance' => 
      array (
        'driver' => 'daily',
        'path' => '/home2/webadminpruebas/web/storage/logs/performance/performance.log',
        'level' => 'info',
        'days' => 30,
        'formatter' => 'App\\Logging\\PerformanceLogFormatter',
      ),
    ),
  ),
  'mail' => 
  array (
    'default' => 'sendmail',
    'mailers' => 
    array (
      'smtp' => 
      array (
        'transport' => 'smtp',
        'scheme' => NULL,
        'url' => NULL,
        'host' => 'localhost',
        'port' => '25',
        'username' => NULL,
        'password' => NULL,
        'timeout' => NULL,
        'local_domain' => 'webadminpruebas.a-alvarez.com',
      ),
      'ses' => 
      array (
        'transport' => 'ses',
      ),
      'postmark' => 
      array (
        'transport' => 'postmark',
      ),
      'resend' => 
      array (
        'transport' => 'resend',
      ),
      'sendmail' => 
      array (
        'transport' => 'sendmail',
        'path' => '/usr/sbin/sendmail -bs -i',
      ),
      'log' => 
      array (
        'transport' => 'log',
        'channel' => NULL,
      ),
      'array' => 
      array (
        'transport' => 'array',
      ),
      'failover' => 
      array (
        'transport' => 'failover',
        'mailers' => 
        array (
          0 => 'smtp',
          1 => 'log',
        ),
      ),
      'roundrobin' => 
      array (
        'transport' => 'roundrobin',
        'mailers' => 
        array (
          0 => 'ses',
          1 => 'postmark',
        ),
      ),
    ),
    'from' => 
    array (
      'address' => 'mail@a-alvarez.com',
      'name' => 'A-alvarez',
    ),
    'markdown' => 
    array (
      'theme' => 'default',
      'paths' => 
      array (
        0 => '/home2/webadminpruebas/web/resources/views/vendor/mail',
      ),
    ),
  ),
  'media-library' => 
  array (
    'disk_name' => 'media',
    'max_file_size' => 10485760,
    'queue_connection_name' => 'database',
    'queue_name' => '',
    'queue_conversions_by_default' => true,
    'queue_conversions_after_database_commit' => true,
    'media_model' => 'Spatie\\MediaLibrary\\MediaCollections\\Models\\Media',
    'media_observer' => 'Spatie\\MediaLibrary\\MediaCollections\\Models\\Observers\\MediaObserver',
    'use_default_collection_serialization' => false,
    'temporary_upload_model' => 'Spatie\\MediaLibraryPro\\Models\\TemporaryUpload',
    'enable_temporary_uploads_session_affinity' => true,
    'generate_thumbnails_for_temporary_uploads' => true,
    'file_namer' => 'Spatie\\MediaLibrary\\Support\\FileNamer\\DefaultFileNamer',
    'path_generator' => 'Spatie\\MediaLibrary\\Support\\PathGenerator\\DefaultPathGenerator',
    'file_remover_class' => 'Spatie\\MediaLibrary\\Support\\FileRemover\\DefaultFileRemover',
    'custom_path_generators' => 
    array (
    ),
    'url_generator' => 'Spatie\\MediaLibrary\\Support\\UrlGenerator\\DefaultUrlGenerator',
    'moves_media_on_update' => false,
    'version_urls' => false,
    'image_optimizers' => 
    array (
      'Spatie\\ImageOptimizer\\Optimizers\\Jpegoptim' => 
      array (
        0 => '-m85',
        1 => '--force',
        2 => '--strip-all',
        3 => '--all-progressive',
      ),
      'Spatie\\ImageOptimizer\\Optimizers\\Pngquant' => 
      array (
        0 => '--force',
      ),
      'Spatie\\ImageOptimizer\\Optimizers\\Optipng' => 
      array (
        0 => '-i0',
        1 => '-o2',
        2 => '-quiet',
      ),
      'Spatie\\ImageOptimizer\\Optimizers\\Svgo' => 
      array (
        0 => '--disable=cleanupIDs',
      ),
      'Spatie\\ImageOptimizer\\Optimizers\\Gifsicle' => 
      array (
        0 => '-b',
        1 => '-O3',
      ),
      'Spatie\\ImageOptimizer\\Optimizers\\Cwebp' => 
      array (
        0 => '-m 6',
        1 => '-pass 10',
        2 => '-mt',
        3 => '-q 90',
      ),
      'Spatie\\ImageOptimizer\\Optimizers\\Avifenc' => 
      array (
        0 => '-a cq-level=23',
        1 => '-j all',
        2 => '--min 0',
        3 => '--max 63',
        4 => '--minalpha 0',
        5 => '--maxalpha 63',
        6 => '-a end-usage=q',
        7 => '-a tune=ssim',
      ),
    ),
    'image_generators' => 
    array (
      0 => 'Spatie\\MediaLibrary\\Conversions\\ImageGenerators\\Image',
      1 => 'Spatie\\MediaLibrary\\Conversions\\ImageGenerators\\Webp',
      2 => 'Spatie\\MediaLibrary\\Conversions\\ImageGenerators\\Avif',
      3 => 'Spatie\\MediaLibrary\\Conversions\\ImageGenerators\\Pdf',
      4 => 'Spatie\\MediaLibrary\\Conversions\\ImageGenerators\\Svg',
      5 => 'Spatie\\MediaLibrary\\Conversions\\ImageGenerators\\Video',
    ),
    'temporary_directory_path' => NULL,
    'image_driver' => 'gd',
    'ffmpeg_path' => '/usr/bin/ffmpeg',
    'ffprobe_path' => '/usr/bin/ffprobe',
    'ffmpeg_timeout' => 900,
    'ffmpeg_threads' => 0,
    'jobs' => 
    array (
      'perform_conversions' => 'Spatie\\MediaLibrary\\Conversions\\Jobs\\PerformConversionsJob',
      'generate_responsive_images' => 'Spatie\\MediaLibrary\\ResponsiveImages\\Jobs\\GenerateResponsiveImagesJob',
    ),
    'media_downloader' => 'Spatie\\MediaLibrary\\Downloaders\\DefaultDownloader',
    'media_downloader_ssl' => true,
    'temporary_url_default_lifetime' => 5,
    'remote' => 
    array (
      'extra_headers' => 
      array (
        'CacheControl' => 'max-age=604800',
      ),
    ),
    'responsive_images' => 
    array (
      'width_calculator' => 'Spatie\\MediaLibrary\\ResponsiveImages\\WidthCalculator\\FileSizeOptimizedWidthCalculator',
      'use_tiny_placeholders' => true,
      'tiny_placeholder_generator' => 'Spatie\\MediaLibrary\\ResponsiveImages\\TinyPlaceholderGenerator\\Blurred',
    ),
    'enable_vapor_uploads' => false,
    'default_loading_attribute_value' => NULL,
    'prefix' => '',
    'force_lazy_loading' => true,
  ),
  'middleware' => 
  array (
    'global' => 
    array (
      0 => 'Illuminate\\Foundation\\Http\\Middleware\\HandleCors',
      1 => 'Illuminate\\Session\\Middleware\\StartSession',
      2 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
      3 => 'App\\Http\\Middleware\\VerifyCsrfToken',
    ),
    'route_middlewares' => 
    array (
      'auth' => 'App\\Http\\Middleware\\Authenticate',
      'auth.basic' => 'Illuminate\\Auth\\Middleware\\AuthenticateWithBasicAuth',
      'auth.session' => 'Illuminate\\Session\\Middleware\\AuthenticateSession',
      'cache.headers' => 'Illuminate\\Http\\Middleware\\SetCacheHeaders',
      'can' => 'Illuminate\\Auth\\Middleware\\Authorize',
      'guest' => 'App\\Http\\Middleware\\RedirectIfAuthenticated',
      'password.confirm' => 'Illuminate\\Auth\\Middleware\\RequirePassword',
      'signed' => 'Illuminate\\Routing\\Middleware\\ValidateSignature',
      'throttle' => 'Illuminate\\Routing\\Middleware\\ThrottleRequests',
      'verified' => 'Illuminate\\Auth\\Middleware\\EnsureEmailIsVerified',
      'role' => 'App\\Http\\Middleware\\RoleMiddleware',
    ),
    'middleware_groups' => 
    array (
      'web' => 
      array (
        0 => 'App\\Http\\Middleware\\EncryptCookies',
        1 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
        2 => 'Illuminate\\Session\\Middleware\\StartSession',
        3 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
        4 => 'App\\Http\\Middleware\\VerifyCsrfToken',
        5 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
      ),
      'api' => 
      array (
        0 => 'Laravel\\Sanctum\\Http\\Middleware\\EnsureFrontendRequestsAreStateful',
        1 => 'throttle:api',
        2 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
      ),
    ),
  ),
  'oracle' => 
  array (
    'oracle' => 
    array (
      'driver' => 'oracle',
      'tns' => '',
      'host' => '192.168.1.120',
      'port' => '3306',
      'database' => 'webadminpruebas',
      'service_name' => '',
      'username' => 'webadmin',
      'password' => 'Mar.90272618',
      'charset' => 'AL32UTF8',
      'prefix' => '',
      'prefix_schema' => '',
      'edition' => 'ora$base',
      'server_version' => '11g',
      'load_balance' => 'yes',
      'max_name_len' => 30,
      'dynamic' => 
      array (
      ),
      'sessionVars' => 
      array (
        'NLS_TIME_FORMAT' => 'HH24:MI:SS',
        'NLS_DATE_FORMAT' => 'YYYY-MM-DD HH24:MI:SS',
        'NLS_TIMESTAMP_FORMAT' => 'YYYY-MM-DD HH24:MI:SS',
        'NLS_TIMESTAMP_TZ_FORMAT' => 'YYYY-MM-DD HH24:MI:SS TZH:TZM',
        'NLS_NUMERIC_CHARACTERS' => '.,',
      ),
    ),
  ),
  'permission' => 
  array (
    'models' => 
    array (
      'permission' => 'Spatie\\Permission\\Models\\Permission',
      'role' => 'App\\Models\\Role',
    ),
    'table_names' => 
    array (
      'roles' => 'roles',
      'permissions' => 'permissions',
      'model_has_permissions' => 'model_has_permissions',
      'model_has_roles' => 'model_has_roles',
      'role_has_permissions' => 'role_has_permissions',
    ),
    'column_names' => 
    array (
      'role_pivot_key' => NULL,
      'permission_pivot_key' => NULL,
      'model_morph_key' => 'model_id',
      'team_foreign_key' => 'team_id',
    ),
    'register_permission_check_method' => true,
    'register_octane_reset_listener' => false,
    'events_enabled' => false,
    'teams' => false,
    'team_resolver' => 'Spatie\\Permission\\DefaultTeamResolver',
    'use_passport_client_credentials' => false,
    'display_permission_in_exception' => false,
    'display_role_in_exception' => false,
    'enable_wildcard_permission' => false,
    'cache' => 
    array (
      'expiration_time' => 
      \DateInterval::__set_state(array(
         'from_string' => true,
         'date_string' => '24 hours',
      )),
      'key' => 'spatie.permission.cache',
      'store' => 'default',
    ),
  ),
  'pulse' => 
  array (
    'domain' => NULL,
    'path' => 'pulse',
    'enabled' => true,
    'storage' => 
    array (
      'driver' => 'database',
      'trim' => 
      array (
        'keep' => '7 days',
      ),
      'database' => 
      array (
        'connection' => NULL,
        'chunk' => 1000,
      ),
    ),
    'ingest' => 
    array (
      'driver' => 'storage',
      'buffer' => 5000,
      'trim' => 
      array (
        'lottery' => 
        array (
          0 => 1,
          1 => 1000,
        ),
        'keep' => '7 days',
      ),
      'redis' => 
      array (
        'connection' => NULL,
        'chunk' => 1000,
      ),
    ),
    'cache' => NULL,
    'middleware' => 
    array (
      0 => 'web',
      1 => 'Laravel\\Pulse\\Http\\Middleware\\Authorize',
    ),
    'recorders' => 
    array (
      'Laravel\\Pulse\\Recorders\\CacheInteractions' => 
      array (
        'enabled' => true,
        'sample_rate' => 1,
        'ignore' => 
        array (
          0 => '/(^laravel_vapor_job_attemp(t?)s:)/',
          1 => '/^.+@.+\\|(?:(?:\\d+\\.\\d+\\.\\d+\\.\\d+)|[0-9a-fA-F:]+)(?::timer)?$/',
          2 => '/^[a-zA-Z0-9]{40}$/',
          3 => '/^illuminate:/',
          4 => '/^laravel:pulse:/',
          5 => '/^laravel:reverb:/',
          6 => '/^nova/',
          7 => '/^telescope:/',
        ),
        'groups' => 
        array (
          '/^job-exceptions:.*/' => 'job-exceptions:*',
        ),
      ),
      'Laravel\\Pulse\\Recorders\\Exceptions' => 
      array (
        'enabled' => true,
        'sample_rate' => 1,
        'location' => true,
        'ignore' => 
        array (
        ),
      ),
      'Laravel\\Pulse\\Recorders\\Queues' => 
      array (
        'enabled' => true,
        'sample_rate' => 1,
        'ignore' => 
        array (
        ),
      ),
      'Laravel\\Pulse\\Recorders\\Servers' => 
      array (
        'server_name' => 'neptuno',
        'directories' => 
        array (
          0 => '/',
        ),
      ),
      'Laravel\\Pulse\\Recorders\\SlowJobs' => 
      array (
        'enabled' => true,
        'sample_rate' => 1,
        'threshold' => 1000,
        'ignore' => 
        array (
        ),
      ),
      'Laravel\\Pulse\\Recorders\\SlowOutgoingRequests' => 
      array (
        'enabled' => true,
        'sample_rate' => 1,
        'threshold' => 1000,
        'ignore' => 
        array (
        ),
        'groups' => 
        array (
        ),
      ),
      'Laravel\\Pulse\\Recorders\\SlowQueries' => 
      array (
        'enabled' => true,
        'sample_rate' => 1,
        'threshold' => 1000,
        'location' => true,
        'max_query_length' => NULL,
        'ignore' => 
        array (
          0 => '/(["`])pulse_[\\w]+?\\1/',
          1 => '/(["`])telescope_[\\w]+?\\1/',
        ),
      ),
      'Laravel\\Pulse\\Recorders\\SlowRequests' => 
      array (
        'enabled' => true,
        'sample_rate' => 1,
        'threshold' => 1000,
        'ignore' => 
        array (
          0 => '#^/pulse$#',
          1 => '#^/telescope#',
        ),
      ),
      'Laravel\\Pulse\\Recorders\\UserJobs' => 
      array (
        'enabled' => true,
        'sample_rate' => 1,
        'ignore' => 
        array (
        ),
      ),
      'Laravel\\Pulse\\Recorders\\UserRequests' => 
      array (
        'enabled' => true,
        'sample_rate' => 1,
        'ignore' => 
        array (
          0 => '#^/pulse$#',
          1 => '#^/telescope#',
        ),
      ),
    ),
  ),
  'purifier' => 
  array (
    'encoding' => 'UTF-8',
    'finalize' => true,
    'cachePath' => '/home2/webadminpruebas/web/storage/app/purifier',
    'cacheFileMode' => 493,
    'settings' => 
    array (
      'default' => 
      array (
        'HTML.Doctype' => 'HTML 4.01 Transitional',
        'HTML.Allowed' => 'div,b,strong,i,em,u,a[href|title],ul,ol,li,p[style],br,span[style],img[width|height|alt|src]',
        'CSS.AllowedProperties' => 'font,font-size,font-weight,font-style,font-family,text-decoration,padding-left,color,background-color,text-align',
        'AutoFormat.AutoParagraph' => true,
        'AutoFormat.RemoveEmpty' => true,
      ),
      'test' => 
      array (
        'Attr.EnableID' => 'true',
      ),
      'youtube' => 
      array (
        'HTML.SafeIframe' => 'true',
        'URI.SafeIframeRegexp' => '%^(http://|https://|//)(www.youtube.com/embed/|player.vimeo.com/video/)%',
      ),
      'custom_definition' => 
      array (
        'id' => 'html5-definitions',
        'rev' => 1,
        'debug' => false,
        'elements' => 
        array (
          0 => 
          array (
            0 => 'section',
            1 => 'Block',
            2 => 'Flow',
            3 => 'Common',
          ),
          1 => 
          array (
            0 => 'nav',
            1 => 'Block',
            2 => 'Flow',
            3 => 'Common',
          ),
          2 => 
          array (
            0 => 'article',
            1 => 'Block',
            2 => 'Flow',
            3 => 'Common',
          ),
          3 => 
          array (
            0 => 'aside',
            1 => 'Block',
            2 => 'Flow',
            3 => 'Common',
          ),
          4 => 
          array (
            0 => 'header',
            1 => 'Block',
            2 => 'Flow',
            3 => 'Common',
          ),
          5 => 
          array (
            0 => 'footer',
            1 => 'Block',
            2 => 'Flow',
            3 => 'Common',
          ),
          6 => 
          array (
            0 => 'address',
            1 => 'Block',
            2 => 'Flow',
            3 => 'Common',
          ),
          7 => 
          array (
            0 => 'hgroup',
            1 => 'Block',
            2 => 'Required: h1 | h2 | h3 | h4 | h5 | h6',
            3 => 'Common',
          ),
          8 => 
          array (
            0 => 'figure',
            1 => 'Block',
            2 => 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow',
            3 => 'Common',
          ),
          9 => 
          array (
            0 => 'figcaption',
            1 => 'Inline',
            2 => 'Flow',
            3 => 'Common',
          ),
          10 => 
          array (
            0 => 'video',
            1 => 'Block',
            2 => 'Optional: (source, Flow) | (Flow, source) | Flow',
            3 => 'Common',
            4 => 
            array (
              'src' => 'URI',
              'type' => 'Text',
              'width' => 'Length',
              'height' => 'Length',
              'poster' => 'URI',
              'preload' => 'Enum#auto,metadata,none',
              'controls' => 'Bool',
            ),
          ),
          11 => 
          array (
            0 => 'source',
            1 => 'Block',
            2 => 'Flow',
            3 => 'Common',
            4 => 
            array (
              'src' => 'URI',
              'type' => 'Text',
            ),
          ),
          12 => 
          array (
            0 => 's',
            1 => 'Inline',
            2 => 'Inline',
            3 => 'Common',
          ),
          13 => 
          array (
            0 => 'var',
            1 => 'Inline',
            2 => 'Inline',
            3 => 'Common',
          ),
          14 => 
          array (
            0 => 'sub',
            1 => 'Inline',
            2 => 'Inline',
            3 => 'Common',
          ),
          15 => 
          array (
            0 => 'sup',
            1 => 'Inline',
            2 => 'Inline',
            3 => 'Common',
          ),
          16 => 
          array (
            0 => 'mark',
            1 => 'Inline',
            2 => 'Inline',
            3 => 'Common',
          ),
          17 => 
          array (
            0 => 'wbr',
            1 => 'Inline',
            2 => 'Empty',
            3 => 'Core',
          ),
          18 => 
          array (
            0 => 'ins',
            1 => 'Block',
            2 => 'Flow',
            3 => 'Common',
            4 => 
            array (
              'cite' => 'URI',
              'datetime' => 'CDATA',
            ),
          ),
          19 => 
          array (
            0 => 'del',
            1 => 'Block',
            2 => 'Flow',
            3 => 'Common',
            4 => 
            array (
              'cite' => 'URI',
              'datetime' => 'CDATA',
            ),
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            0 => 'iframe',
            1 => 'allowfullscreen',
            2 => 'Bool',
          ),
          1 => 
          array (
            0 => 'table',
            1 => 'height',
            2 => 'Text',
          ),
          2 => 
          array (
            0 => 'td',
            1 => 'border',
            2 => 'Text',
          ),
          3 => 
          array (
            0 => 'th',
            1 => 'border',
            2 => 'Text',
          ),
          4 => 
          array (
            0 => 'tr',
            1 => 'width',
            2 => 'Text',
          ),
          5 => 
          array (
            0 => 'tr',
            1 => 'height',
            2 => 'Text',
          ),
          6 => 
          array (
            0 => 'tr',
            1 => 'border',
            2 => 'Text',
          ),
        ),
      ),
      'custom_attributes' => 
      array (
        0 => 
        array (
          0 => 'a',
          1 => 'target',
          2 => 'Enum#_blank,_self,_target,_top',
        ),
      ),
      'custom_elements' => 
      array (
        0 => 
        array (
          0 => 'u',
          1 => 'Inline',
          2 => 'Inline',
          3 => 'Common',
        ),
      ),
    ),
  ),
  'queue' => 
  array (
    'default' => 'database',
    'connections' => 
    array (
      'sync' => 
      array (
        'driver' => 'sync',
      ),
      'database' => 
      array (
        'driver' => 'database',
        'connection' => NULL,
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 90,
        'after_commit' => false,
      ),
      'beanstalkd' => 
      array (
        'driver' => 'beanstalkd',
        'host' => 'localhost',
        'queue' => 'default',
        'retry_after' => 90,
        'block_for' => 0,
        'after_commit' => false,
      ),
      'sqs' => 
      array (
        'driver' => 'sqs',
        'key' => '',
        'secret' => '',
        'prefix' => 'https://sqs.us-east-1.amazonaws.com/your-account-id',
        'queue' => 'default',
        'suffix' => NULL,
        'region' => 'us-east-1',
        'after_commit' => false,
      ),
      'redis' => 
      array (
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'default',
        'retry_after' => 90,
        'block_for' => NULL,
        'after_commit' => false,
      ),
    ),
    'batching' => 
    array (
      'database' => 'mysql',
      'table' => 'job_batches',
    ),
    'failed' => 
    array (
      'driver' => 'database-uuids',
      'database' => 'mysql',
      'table' => 'failed_jobs',
    ),
  ),
  'returns' => 
  array (
    'return_reference' => 'DEV',
    'return_days_limit' => 30,
    'default_status_id' => 1,
    'approved_status_id' => 2,
    'send_confirmation_email' => true,
    'send_status_update_email' => true,
    'pdf_content' => '',
    'company_info' => 
    array (
      'name' => 'Tu Empresa',
      'address' => 'Dirección de la empresa',
      'phone' => 'Teléfono',
      'email' => 'info@empresa.com',
      'website' => 'www.empresa.com',
    ),
    'notifications' => 
    array (
      'admin_email' => NULL,
      'notify_admin_on_new_return' => true,
      'notify_customer_on_status_change' => true,
    ),
    'logistics_modes' => 
    array (
      'customer_transport' => 'Agencia de transporte (cuenta del cliente)',
      'home_pickup' => 'Recogida a domicilio',
      'store_delivery' => 'Entrega en tienda',
      'inpost' => 'InPost',
    ),
    'return_order_statuses' => '5,4',
    'allow_virtual_products' => false,
    'customer_wallet_enabled' => false,
    'terms_and_conditions_required' => true,
    'terms_and_conditions_cms_page' => 1,
    'allowed_erp_statuses' => '4,5,6',
    'min_return_amount' => 0,
    'high_value_product_threshold' => 500,
    'restricted_catalogs' => 
    array (
      0 => '',
    ),
    'validation' => 
    array (
      'min_description_length' => 10,
      'max_description_length' => 1000,
      'allowed_file_types' => 
      array (
        0 => 'jpg',
        1 => 'jpeg',
        2 => 'png',
        3 => 'pdf',
      ),
      'max_file_size' => 5120,
    ),
  ),
  'reverb' => 
  array (
    'default' => 'reverb',
    'servers' => 
    array (
      'reverb' => 
      array (
        'host' => '0.0.0.0',
        'port' => 8080,
        'hostname' => NULL,
        'options' => 
        array (
          'tls' => 
          array (
          ),
        ),
        'max_request_size' => 10000,
        'scaling' => 
        array (
          'enabled' => false,
          'channel' => 'reverb',
          'server' => 
          array (
            'url' => NULL,
            'host' => '127.0.0.1',
            'port' => '6379',
            'username' => NULL,
            'password' => NULL,
            'database' => '0',
          ),
        ),
        'pulse_ingest_interval' => 15,
        'telescope_ingest_interval' => 15,
      ),
    ),
    'apps' => 
    array (
      'provider' => 'config',
      'apps' => 
      array (
        0 => 
        array (
          'key' => NULL,
          'secret' => NULL,
          'app_id' => NULL,
          'options' => 
          array (
            'host' => NULL,
            'port' => 443,
            'scheme' => 'https',
            'useTLS' => true,
          ),
          'allowed_origins' => 
          array (
            0 => '*',
          ),
          'ping_interval' => 60,
          'activity_timeout' => 30,
          'max_message_size' => 10000,
        ),
      ),
    ),
  ),
  'sanctum' => 
  array (
    'stateful' => 
    array (
      0 => 'localhost',
      1 => 'localhost:3000',
      2 => '127.0.0.1',
      3 => '127.0.0.1:8000',
      4 => '::1',
      5 => 'webadminpruebas.a-alvarez.com',
    ),
    'guard' => 
    array (
      0 => 'web',
    ),
    'expiration' => NULL,
    'token_prefix' => '',
    'middleware' => 
    array (
      'authenticate_session' => 'Laravel\\Sanctum\\Http\\Middleware\\AuthenticateSession',
      'encrypt_cookies' => 'App\\Http\\Middleware\\EncryptCookies',
      'verify_csrf_token' => 'App\\Http\\Middleware\\VerifyCsrfToken',
    ),
  ),
  'seotools' => 
  array (
    'inertia' => false,
    'meta' => 
    array (
      'defaults' => 
      array (
        'title' => 'It\'s Over 9000!',
        'titleBefore' => false,
        'description' => 'For those who helped create the Genki Dama',
        'separator' => ' - ',
        'keywords' => 
        array (
        ),
        'canonical' => false,
        'robots' => false,
      ),
      'webmaster_tags' => 
      array (
        'google' => NULL,
        'bing' => NULL,
        'alexa' => NULL,
        'pinterest' => NULL,
        'yandex' => NULL,
        'norton' => NULL,
      ),
      'add_notranslate_class' => false,
    ),
    'opengraph' => 
    array (
      'defaults' => 
      array (
        'title' => 'Over 9000 Thousand!',
        'description' => 'For those who helped create the Genki Dama',
        'url' => false,
        'type' => false,
        'site_name' => false,
        'images' => 
        array (
        ),
      ),
    ),
    'twitter' => 
    array (
      'defaults' => 
      array (
      ),
    ),
    'json-ld' => 
    array (
      'defaults' => 
      array (
        'title' => 'Over 9000 Thousand!',
        'description' => 'For those who helped create the Genki Dama',
        'url' => false,
        'type' => 'WebPage',
        'images' => 
        array (
        ),
      ),
    ),
  ),
  'services' => 
  array (
    'postmark' => 
    array (
      'token' => NULL,
    ),
    'ses' => 
    array (
      'key' => '',
      'secret' => '',
      'region' => 'us-east-1',
    ),
    'resend' => 
    array (
      'key' => NULL,
    ),
    'slack' => 
    array (
      'notifications' => 
      array (
        'bot_user_oauth_token' => NULL,
        'channel' => NULL,
      ),
    ),
    'twilio' => 
    array (
      'sid' => NULL,
      'token' => NULL,
      'from' => NULL,
    ),
    'fcm' => 
    array (
      'server_key' => NULL,
      'sender_id' => NULL,
    ),
  ),
  'session' => 
  array (
    'driver' => 'database',
    'lifetime' => 120,
    'expire_on_close' => false,
    'encrypt' => false,
    'files' => '/home2/webadminpruebas/web/storage/framework/sessions',
    'connection' => NULL,
    'table' => 'sessions',
    'store' => NULL,
    'lottery' => 
    array (
      0 => 2,
      1 => 100,
    ),
    'cookie' => 'a_alvarez_session',
    'path' => '/',
    'domain' => NULL,
    'secure' => NULL,
    'http_only' => true,
    'same_site' => 'lax',
    'partitioned' => false,
  ),
  'verification' => 
  array (
    'services' => 
    array (
      0 => 
      array (
        'id' => 'emailable.com',
        'name' => 'Emailable (recommended)',
        'uri' => 'https://api.emailable.com/v1/verify?email={EMAIL}&api_key={API_KEY}',
        'request_type' => 'GET',
        'fields' => 
        array (
          0 => 'api_key',
        ),
        'result_xpath' => '$.state',
        'result_map' => 
        array (
          'deliverable' => 'deliverable',
          'undeliverable' => 'undeliverable',
          'risky' => 'risky',
          'unknown' => 'unknown',
        ),
      ),
      1 => 
      array (
        'id' => 'kickbox.io',
        'name' => 'Kickbox IO',
        'uri' => 'https://api.kickbox.io/v2/verify?email={EMAIL}&apikey={API_KEY}',
        'request_type' => 'GET',
        'fields' => 
        array (
          0 => 'api_key',
        ),
        'result_xpath' => '$.result',
        'result_map' => 
        array (
          'deliverable' => 'deliverable',
          'undeliverable' => 'undeliverable',
          'risky' => 'risky',
          'unknown' => 'unknown',
        ),
      ),
      2 => 
      array (
        'id' => 'zerobounce.net',
        'name' => 'Zero Bounce',
        'uri' => 'https://api.zerobounce.net/v2/validate?api_key={API_KEY}&email={EMAIL}&ip_address=',
        'request_type' => 'GET',
        'fields' => 
        array (
          0 => 'api_key',
        ),
        'result_xpath' => '$.status',
        'result_map' => 
        array (
          'valid' => 'deliverable',
          'Valid' => 'deliverable',
          'invalid' => 'undeliverable',
          'Invalid' => 'undeliverable',
          'unknown' => 'unknown',
          'Unknown' => 'unknown',
          'abuse' => 'undeliverable',
          'Abuse' => 'undeliverable',
          'spamtrap' => 'undeliverable',
          'Spamtrap' => 'undeliverable',
          'catch-all' => 'deliverable',
          'Catch-all' => 'deliverable',
          'Catch-All' => 'deliverable',
          'do_not_mail' => 'undeliverable',
          'Do_not_mail' => 'undeliverable',
          'Do_Not_Mail' => 'undeliverable',
        ),
      ),
      3 => 
      array (
        'id' => 'verify-email.org',
        'name' => 'VerifyEmail ORG',
        'uri' => 'https://app.verify-email.org/api/v1/{API_KEY}/verify/{EMAIL}',
        'request_type' => 'GET',
        'fields' => 
        array (
          0 => 'api_key',
        ),
        'result_xpath' => '$.status',
        'result_map' => 
        array (
          1 => 'deliverable',
          0 => 'undeliverable',
          -1 => 'unknown',
        ),
      ),
      4 => 
      array (
        'id' => 'localmail.io',
        'name' => 'Localmail IO',
        'uri' => 'https://api.localmail.io/v1/mail/verify?key={API_KEY}&email={EMAIL}',
        'request_type' => 'GET',
        'fields' => 
        array (
          0 => 'api_key',
        ),
        'result_xpath' => '$.result',
        'result_map' => 
        array (
          'deliverable' => 'deliverable',
          'unknown' => 'unknown',
          'risky' => 'risky',
          'undeliverable' => 'undeliverable',
        ),
      ),
      5 => 
      array (
        'id' => 'debounce.io',
        'name' => 'Debounce IO',
        'uri' => 'https://api.debounce.io/v1/?api={API_KEY}&email={EMAIL}',
        'request_type' => 'GET',
        'fields' => 
        array (
          0 => 'api_key',
        ),
        'result_xpath' => '$.debounce.result',
        'result_map' => 
        array (
          'Safe to Send' => 'deliverable',
          'Unknown' => 'unknown',
          'Risky' => 'risky',
          'Invalid' => 'undeliverable',
        ),
      ),
      6 => 
      array (
        'id' => 'emailchecker.com',
        'name' => 'EmailChecker',
        'uri' => 'https://api.emailverifyapi.com/v3/lookups/json?email={EMAIL}&key={API_KEY}',
        'request_type' => 'GET',
        'fields' => 
        array (
          0 => 'api_key',
        ),
        'result_xpath' => '$.deliverable',
        'result_map' => 
        array (
          'true' => 'deliverable',
          'false' => 'undeliverable',
        ),
      ),
      7 => 
      array (
        'id' => 'cloudvision.io',
        'name' => 'Cloud Vision',
        'uri' => 'https://dev-marketing.cloudvision.io/api/v1/verify?email={EMAIL}&api_token={API_KEY}',
        'request_type' => 'GET',
        'fields' => 
        array (
          0 => 'api_key',
        ),
        'result_xpath' => '$.result',
        'result_map' => 
        array (
          'deliverable' => 'deliverable',
          'undeliverable' => 'undeliverable',
        ),
      ),
      8 => 
      array (
        'id' => 'cloudmersive.com',
        'name' => 'Cloudmersive',
        'uri' => 'https://api.cloudmersive.com/validate/email/address/full',
        'request_type' => 'POST',
        'post_data' => '{EMAIL}',
        'post_headers' => 
        array (
          'Content-Type' => 'application/json',
          'Apikey' => '{API_KEY}',
        ),
        'fields' => 
        array (
          0 => 'api_key',
        ),
        'result_xpath' => '$.ValidAddress',
        'result_map' => 
        array (
          'true' => 'deliverable',
          'false' => 'undeliverable',
        ),
      ),
      9 => 
      array (
        'id' => 'emaillistvalidation.com',
        'name' => 'Emaillist Validation',
        'uri' => 'https://app.emaillistvalidation.com/api/verifEmail?secret={API_KEY}&email={EMAIL}',
        'request_type' => 'GET',
        'response_type' => 'plain',
        'fields' => 
        array (
          0 => 'api_key',
        ),
        'result_map' => 
        array (
          'ok' => 'deliverable',
          'ok_for_all' => 'deliverable',
          'ok_for_all | ok_for_all' => 'deliverable',
          'ok_for_all|ok_for_all' => 'deliverable',
          'email_disabled' => 'undeliverable',
          'risky' => 'risky',
          'unknown' => 'unknown',
        ),
      ),
      10 => 
      array (
        'id' => 'bounceless.io',
        'name' => 'Bounceless.io',
        'uri' => 'https://apps.bounceless.io/api/singlemaildetails?secret={API_KEY}&email={EMAIL}',
        'request_type' => 'GET',
        'fields' => 
        array (
          0 => 'api_key',
        ),
        'result_xpath' => '$.result',
        'result_map' => 
        array (
          'valid' => 'deliverable',
          'unknown' => 'unknown',
          'invalid' => 'undeliverable',
          'risky' => 'risky',
        ),
      ),
      11 => 
      array (
        'id' => 'bouncify.io',
        'name' => 'Bouncify',
        'uri' => 'https://api.bouncify.io/v1/verify?apikey={API_KEY}&email={EMAIL}',
        'request_type' => 'GET',
        'fields' => 
        array (
          0 => 'api_key',
        ),
        'result_xpath' => '$.result',
        'result_map' => 
        array (
          'deliverable' => 'deliverable',
          'unknown' => 'unknown',
          'accept-all' => 'unknown',
          'accept all' => 'unknown',
          'undeliverable' => 'undeliverable',
        ),
      ),
      12 => 
      array (
        'id' => 'myemailverifier.com',
        'name' => 'myEmailVerifier',
        'uri' => 'https://client.myemailverifier.com/verifier/validate_single/{EMAIL}/{API_KEY}',
        'request_type' => 'GET',
        'fields' => 
        array (
          0 => 'api_key',
        ),
        'result_xpath' => '$.Status',
        'result_map' => 
        array (
          'Valid' => 'deliverable',
          'Unknown' => 'unknown',
          'Invalid' => 'undeliverable',
        ),
      ),
    ),
  ),
  'view' => 
  array (
    'paths' => 
    array (
      0 => '/home2/webadminpruebas/web/resources/views',
    ),
    'compiled' => '/home2/webadminpruebas/web/storage/framework/views',
  ),
  'wordpress' => 
  array (
    1 => 
    array (
      'url' => 'http://localhost:3000',
      'db_name' => 'wordpress',
      'db_prefix' => '',
    ),
    2 => 
    array (
      'url' => 'http://localhost:3001',
      'db_name' => 'wordpress',
      'db_prefix' => '',
    ),
  ),
  'dompdf' => 
  array (
    'show_warnings' => false,
    'public_path' => NULL,
    'convert_entities' => true,
    'options' => 
    array (
      'font_dir' => '/home2/webadminpruebas/web/storage/fonts',
      'font_cache' => '/home2/webadminpruebas/web/storage/fonts',
      'temp_dir' => '/tmp',
      'chroot' => '/home2/webadminpruebas/web',
      'allowed_protocols' => 
      array (
        'data://' => 
        array (
          'rules' => 
          array (
          ),
        ),
        'file://' => 
        array (
          'rules' => 
          array (
          ),
        ),
        'http://' => 
        array (
          'rules' => 
          array (
          ),
        ),
        'https://' => 
        array (
          'rules' => 
          array (
          ),
        ),
      ),
      'artifactPathValidation' => NULL,
      'log_output_file' => NULL,
      'enable_font_subsetting' => false,
      'pdf_backend' => 'CPDF',
      'default_media_type' => 'screen',
      'default_paper_size' => 'a4',
      'default_paper_orientation' => 'portrait',
      'default_font' => 'serif',
      'dpi' => 96,
      'enable_php' => false,
      'enable_javascript' => true,
      'enable_remote' => false,
      'allowed_remote_hosts' => NULL,
      'font_height_ratio' => 1.1,
      'enable_html5_parser' => true,
    ),
  ),
  'botman' => 
  array (
    'config' => 
    array (
      'conversation_cache_time' => 40,
      'user_cache_time' => 30,
      'curl_options' => 
      array (
      ),
    ),
    'web' => 
    array (
      'matchingData' => 
      array (
        'driver' => 'web',
      ),
    ),
  ),
  'livewire' => 
  array (
    'class_namespace' => 'App\\Livewire',
    'view_path' => '/home2/webadminpruebas/web/resources/views/livewire',
    'layout' => 'components.layouts.app',
    'lazy_placeholder' => NULL,
    'temporary_file_upload' => 
    array (
      'disk' => NULL,
      'rules' => NULL,
      'directory' => NULL,
      'middleware' => NULL,
      'preview_mimes' => 
      array (
        0 => 'png',
        1 => 'gif',
        2 => 'bmp',
        3 => 'svg',
        4 => 'wav',
        5 => 'mp4',
        6 => 'mov',
        7 => 'avi',
        8 => 'wmv',
        9 => 'mp3',
        10 => 'm4a',
        11 => 'jpg',
        12 => 'jpeg',
        13 => 'mpga',
        14 => 'webp',
        15 => 'wma',
      ),
      'max_upload_time' => 5,
      'cleanup' => true,
    ),
    'render_on_redirect' => false,
    'legacy_model_binding' => false,
    'inject_assets' => true,
    'navigate' => 
    array (
      'show_progress_bar' => true,
      'progress_bar_color' => '#2299dd',
    ),
    'inject_morph_markers' => true,
    'smart_wire_keys' => false,
    'pagination_theme' => 'tailwind',
    'release_token' => 'a',
  ),
  'disposable-email' => 
  array (
    'sources' => 
    array (
      0 => 'https://cdn.jsdelivr.net/gh/disposable/disposable-email-domains@master/domains.json',
    ),
    'fetcher' => 'Propaganistas\\LaravelDisposableEmail\\Fetcher\\DefaultFetcher',
    'storage' => '/home2/webadminpruebas/web/storage/framework/disposable_domains.json',
    'whitelist' => 
    array (
    ),
    'include_subdomains' => false,
    'cache' => 
    array (
      'enabled' => true,
      'store' => 'default',
      'key' => 'disposable_email:domains',
    ),
  ),
  'activitylog' => 
  array (
    'enabled' => true,
    'delete_records_older_than_days' => 365,
    'default_log_name' => 'default',
    'default_auth_driver' => NULL,
    'subject_returns_soft_deleted_models' => false,
    'activity_model' => 'Spatie\\Activitylog\\Models\\Activity',
    'table_name' => 'activity_log',
    'database_connection' => NULL,
  ),
  'cookie-consent' => 
  array (
    'enabled' => true,
    'cookie_name' => 'laravel_cookie_consent',
    'cookie_lifetime' => 7300,
  ),
  'flare' => 
  array (
    'key' => NULL,
    'flare_middleware' => 
    array (
      0 => 'Spatie\\FlareClient\\FlareMiddleware\\RemoveRequestIp',
      1 => 'Spatie\\FlareClient\\FlareMiddleware\\AddGitInformation',
      2 => 'Spatie\\LaravelIgnition\\FlareMiddleware\\AddNotifierName',
      3 => 'Spatie\\LaravelIgnition\\FlareMiddleware\\AddEnvironmentInformation',
      4 => 'Spatie\\LaravelIgnition\\FlareMiddleware\\AddExceptionInformation',
      5 => 'Spatie\\LaravelIgnition\\FlareMiddleware\\AddDumps',
      'Spatie\\LaravelIgnition\\FlareMiddleware\\AddLogs' => 
      array (
        'maximum_number_of_collected_logs' => 200,
      ),
      'Spatie\\LaravelIgnition\\FlareMiddleware\\AddQueries' => 
      array (
        'maximum_number_of_collected_queries' => 200,
        'report_query_bindings' => true,
      ),
      'Spatie\\LaravelIgnition\\FlareMiddleware\\AddJobs' => 
      array (
        'max_chained_job_reporting_depth' => 5,
      ),
      6 => 'Spatie\\LaravelIgnition\\FlareMiddleware\\AddContext',
      7 => 'Spatie\\LaravelIgnition\\FlareMiddleware\\AddExceptionHandledStatus',
      'Spatie\\FlareClient\\FlareMiddleware\\CensorRequestBodyFields' => 
      array (
        'censor_fields' => 
        array (
          0 => 'password',
          1 => 'password_confirmation',
        ),
      ),
      'Spatie\\FlareClient\\FlareMiddleware\\CensorRequestHeaders' => 
      array (
        'headers' => 
        array (
          0 => 'API-KEY',
          1 => 'Authorization',
          2 => 'Cookie',
          3 => 'Set-Cookie',
          4 => 'X-CSRF-TOKEN',
          5 => 'X-XSRF-TOKEN',
        ),
      ),
    ),
    'send_logs_as_events' => true,
  ),
  'ignition' => 
  array (
    'editor' => 'phpstorm',
    'theme' => 'auto',
    'enable_share_button' => true,
    'register_commands' => false,
    'solution_providers' => 
    array (
      0 => 'Spatie\\Ignition\\Solutions\\SolutionProviders\\BadMethodCallSolutionProvider',
      1 => 'Spatie\\Ignition\\Solutions\\SolutionProviders\\MergeConflictSolutionProvider',
      2 => 'Spatie\\Ignition\\Solutions\\SolutionProviders\\UndefinedPropertySolutionProvider',
      3 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\IncorrectValetDbCredentialsSolutionProvider',
      4 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\MissingAppKeySolutionProvider',
      5 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\DefaultDbNameSolutionProvider',
      6 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\TableNotFoundSolutionProvider',
      7 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\MissingImportSolutionProvider',
      8 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\InvalidRouteActionSolutionProvider',
      9 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\ViewNotFoundSolutionProvider',
      10 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\RunningLaravelDuskInProductionProvider',
      11 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\MissingColumnSolutionProvider',
      12 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\UnknownValidationSolutionProvider',
      13 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\MissingMixManifestSolutionProvider',
      14 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\MissingViteManifestSolutionProvider',
      15 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\MissingLivewireComponentSolutionProvider',
      16 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\UndefinedViewVariableSolutionProvider',
      17 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\GenericLaravelExceptionSolutionProvider',
      18 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\OpenAiSolutionProvider',
      19 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\SailNetworkSolutionProvider',
      20 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\UnknownMysql8CollationSolutionProvider',
      21 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\UnknownMariadbCollationSolutionProvider',
    ),
    'ignored_solution_providers' => 
    array (
    ),
    'enable_runnable_solutions' => NULL,
    'remote_sites_path' => '/home2/webadminpruebas/web',
    'local_sites_path' => '',
    'housekeeping_endpoint_prefix' => '_ignition',
    'settings_file_path' => '',
    'recorders' => 
    array (
      0 => 'Spatie\\LaravelIgnition\\Recorders\\DumpRecorder\\DumpRecorder',
      1 => 'Spatie\\LaravelIgnition\\Recorders\\JobRecorder\\JobRecorder',
      2 => 'Spatie\\LaravelIgnition\\Recorders\\LogRecorder\\LogRecorder',
      3 => 'Spatie\\LaravelIgnition\\Recorders\\QueryRecorder\\QueryRecorder',
    ),
    'open_ai_key' => NULL,
    'with_stack_frame_arguments' => true,
    'argument_reducers' => 
    array (
      0 => 'Spatie\\Backtrace\\Arguments\\Reducers\\BaseTypeArgumentReducer',
      1 => 'Spatie\\Backtrace\\Arguments\\Reducers\\ArrayArgumentReducer',
      2 => 'Spatie\\Backtrace\\Arguments\\Reducers\\StdClassArgumentReducer',
      3 => 'Spatie\\Backtrace\\Arguments\\Reducers\\EnumArgumentReducer',
      4 => 'Spatie\\Backtrace\\Arguments\\Reducers\\ClosureArgumentReducer',
      5 => 'Spatie\\Backtrace\\Arguments\\Reducers\\DateTimeArgumentReducer',
      6 => 'Spatie\\Backtrace\\Arguments\\Reducers\\DateTimeZoneArgumentReducer',
      7 => 'Spatie\\Backtrace\\Arguments\\Reducers\\SymphonyRequestArgumentReducer',
      8 => 'Spatie\\LaravelIgnition\\ArgumentReducers\\ModelArgumentReducer',
      9 => 'Spatie\\LaravelIgnition\\ArgumentReducers\\CollectionArgumentReducer',
      10 => 'Spatie\\Backtrace\\Arguments\\Reducers\\StringableArgumentReducer',
    ),
  ),
  'geoip' => 
  array (
    'log_failures' => true,
    'include_currency' => true,
    'service' => NULL,
    'services' => 
    array (
      'maxmind_database' => 
      array (
        'class' => 'Torann\\GeoIP\\Services\\MaxMindDatabase',
        'database_path' => '/home2/webadminpruebas/web/storage/app/geoip.mmdb',
        'update_url' => 'https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City&license_key=&suffix=tar.gz',
        'locales' => 
        array (
          0 => 'en',
        ),
      ),
      'maxmind_api' => 
      array (
        'class' => 'Torann\\GeoIP\\Services\\MaxMindWebService',
        'user_id' => NULL,
        'license_key' => NULL,
        'locales' => 
        array (
          0 => 'en',
        ),
      ),
      'ipgeolocation' => 
      array (
        'class' => 'Torann\\GeoIP\\Services\\IPGeoLocation',
        'secure' => true,
        'key' => NULL,
        'continent_path' => '/home2/webadminpruebas/web/storage/app/continents.json',
        'lang' => 'en',
      ),
      'ipdata' => 
      array (
        'class' => 'Torann\\GeoIP\\Services\\IPData',
        'key' => NULL,
        'secure' => true,
      ),
      'ipfinder' => 
      array (
        'class' => 'Torann\\GeoIP\\Services\\IPFinder',
        'key' => NULL,
        'secure' => true,
        'locales' => 
        array (
          0 => 'en',
        ),
      ),
    ),
    'cache' => 'all',
    'cache_tags' => 
    array (
      0 => 'torann-geoip-location',
    ),
    'cache_expires' => 30,
    'default_location' => 
    array (
      'ip' => '127.0.0.0',
      'iso_code' => 'US',
      'country' => 'United States',
      'city' => 'New Haven',
      'state' => 'CT',
      'state_name' => 'Connecticut',
      'postal_code' => '06510',
      'lat' => 41.31,
      'lon' => -72.92,
      'timezone' => 'America/New_York',
      'continent' => 'NA',
      'default' => true,
      'currency' => 'USD',
    ),
  ),
  'imap' => 
  array (
    'default' => 'default',
    'date_format' => 'd-M-Y',
    'accounts' => 
    array (
      'default' => 
      array (
        'host' => 'localhost',
        'port' => 993,
        'protocol' => 'imap',
        'encryption' => 'ssl',
        'validate_cert' => true,
        'username' => 'root@example.com',
        'password' => '',
        'authentication' => NULL,
        'proxy' => 
        array (
          'socket' => NULL,
          'request_fulluri' => false,
          'username' => NULL,
          'password' => NULL,
        ),
        'timeout' => 30,
        'extensions' => 
        array (
        ),
      ),
    ),
    'options' => 
    array (
      'delimiter' => '/',
      'fetch' => 2,
      'sequence' => 1,
      'fetch_body' => true,
      'fetch_flags' => true,
      'soft_fail' => false,
      'rfc822' => true,
      'debug' => false,
      'uid_cache' => true,
      'boundary' => '/boundary=(.*?(?=;)|(.*))/i',
      'message_key' => 'list',
      'fetch_order' => 'asc',
      'dispositions' => 
      array (
        0 => 'attachment',
        1 => 'inline',
      ),
      'common_folders' => 
      array (
        'root' => 'INBOX',
        'junk' => 'INBOX/Junk',
        'draft' => 'INBOX/Drafts',
        'sent' => 'INBOX/Sent',
        'trash' => 'INBOX/Trash',
      ),
      'open' => 
      array (
      ),
    ),
    'decoding' => 
    array (
      'options' => 
      array (
        'header' => 'utf-8',
        'message' => 'utf-8',
        'attachment' => 'utf-8',
      ),
      'decoder' => 
      array (
        'header' => 'Webklex\\PHPIMAP\\Decoder\\HeaderDecoder',
        'message' => 'Webklex\\PHPIMAP\\Decoder\\MessageDecoder',
        'attachment' => 'Webklex\\PHPIMAP\\Decoder\\AttachmentDecoder',
      ),
    ),
    'flags' => 
    array (
      0 => 'recent',
      1 => 'flagged',
      2 => 'answered',
      3 => 'deleted',
      4 => 'seen',
      5 => 'draft',
    ),
    'events' => 
    array (
      'message' => 
      array (
        'new' => 'Webklex\\IMAP\\Events\\MessageNewEvent',
        'moved' => 'Webklex\\IMAP\\Events\\MessageMovedEvent',
        'copied' => 'Webklex\\IMAP\\Events\\MessageCopiedEvent',
        'deleted' => 'Webklex\\IMAP\\Events\\MessageDeletedEvent',
        'restored' => 'Webklex\\IMAP\\Events\\MessageRestoredEvent',
      ),
      'folder' => 
      array (
        'new' => 'Webklex\\IMAP\\Events\\FolderNewEvent',
        'moved' => 'Webklex\\IMAP\\Events\\FolderMovedEvent',
        'deleted' => 'Webklex\\IMAP\\Events\\FolderDeletedEvent',
      ),
      'flag' => 
      array (
        'new' => 'Webklex\\IMAP\\Events\\FlagNewEvent',
        'deleted' => 'Webklex\\IMAP\\Events\\FlagDeletedEvent',
      ),
    ),
    'masks' => 
    array (
      'message' => 'Webklex\\PHPIMAP\\Support\\Masks\\MessageMask',
      'attachment' => 'Webklex\\PHPIMAP\\Support\\Masks\\AttachmentMask',
    ),
  ),
  'websockets' => 
  array (
    'dashboard' => 
    array (
      'port' => 6001,
    ),
  ),
  'tinker' => 
  array (
    'commands' => 
    array (
    ),
    'alias' => 
    array (
    ),
    'dont_alias' => 
    array (
      0 => 'App\\Nova',
    ),
  ),
);
