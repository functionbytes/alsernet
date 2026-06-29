<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Template Module Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración del módulo de plantillas web para inoqualab
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Template
    |--------------------------------------------------------------------------
    |
    | Plantilla predeterminada que se usa si no hay ninguna activa
    |
    */
    'default' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Templates Directory
    |--------------------------------------------------------------------------
    |
    | Ruta relativa a public/ donde se almacenan las plantillas
    |
    */
    'templates_directory' => 'templates',

    /*
    |--------------------------------------------------------------------------
    | Display Template Manager in Admin Panel
    |--------------------------------------------------------------------------
    |
    | Mostrar el gestor de plantillas en el panel de administración
    |
    */
    'display_template_manager' => env('CMS_TEMPLATE_DISPLAY_TEMPLATE_MANAGER', true),

    /*
    |--------------------------------------------------------------------------
    | Enable Custom HTML
    |--------------------------------------------------------------------------
    |
    | Permitir contenido HTML personalizado en plantillas
    |
    */
    'enable_custom_html' => env('CMS_TEMPLATE_ENABLE_CUSTOM_HTML', true),

    /*
    |--------------------------------------------------------------------------
    | Enable Custom JavaScript
    |--------------------------------------------------------------------------
    |
    | Permitir scripts JavaScript personalizados en plantillas
    |
    */
    'enable_custom_js' => env('CMS_TEMPLATE_ENABLE_CUSTOM_JS', true),

    /*
    |--------------------------------------------------------------------------
    | Template Cache TTL
    |--------------------------------------------------------------------------
    |
    | Tiempo de vida del caché de templates en segundos
    | 0 = sin caché, 3600 = 1 hora
    |
    */
    'cache_ttl' => env('CMS_TEMPLATE_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Max Template Versions
    |--------------------------------------------------------------------------
    |
    | Número máximo de versiones a mantener por template
    | 0 = sin límite
    |
    */
    'max_versions' => env('CMS_TEMPLATE_MAX_VERSIONS', 50),

    /*
    |--------------------------------------------------------------------------
    | Template Per Page (Admin Index)
    |--------------------------------------------------------------------------
    |
    | Número de templates a mostrar por página en el listado settings
    |
    */
    'per_page' => 20,
];
