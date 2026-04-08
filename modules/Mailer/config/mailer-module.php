<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Category labels for variable grouping
    |--------------------------------------------------------------------------
    */
    'category_labels' => [
        'system' => 'Sistema',
        'site' => 'Sitio',
        'customer' => 'Cliente',
        'order' => 'Pedido',
        'document' => 'Documento',
        'general' => 'General',
    ],

    /*
    |--------------------------------------------------------------------------
    | Available modules for variables
    |--------------------------------------------------------------------------
    */
    'modules' => [
        'core' => 'Core',
        'documents' => 'Documentos',
        'orders' => 'Pedidos',
    ],

    /*
    |--------------------------------------------------------------------------
    | Critical component aliases that cannot be deleted
    |--------------------------------------------------------------------------
    */
    'critical_components' => [
        'mail_template_header',
        'mail_template_footer',
        'mail_template_wrapper',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache layout aliases for targeted cache clearing
    |--------------------------------------------------------------------------
    */
    'cache_layout_aliases' => [
        'email_template_header',
        'email_template_footer',
        'email_template_wrapper',
    ],
];
