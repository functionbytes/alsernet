<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Portal público para cargar documentación
    |--------------------------------------------------------------------------
    |
    | URL base o plantilla donde los clientes pueden cargar la documentación.
    | Puedes incluir el token {uid} para que se reemplace automáticamente con
    | el UID del documento en los correos enviados.
    |
    */
    'upload_portal_url' => env('DOCUMENTS_UPLOAD_PORTAL_URL', env('APP_URL').'/documents/{uid}'),

    /*
    |--------------------------------------------------------------------------
    | Secretos de webhooks entrantes
    |--------------------------------------------------------------------------
    |
    | HMAC-SHA256 sobre "{timestamp}:{raw_body}". Sin secreto configurado el
    | endpoint responde 503 (fail-closed), nunca acepta sin verificar.
    |
    */
    'webhooks' => [
        'prestashop_secret' => env('DOCUMENTS_PRESTASHOP_WEBHOOK_SECRET', ''),
        'erp_secret' => env('DOCUMENTS_ERP_WEBHOOK_SECRET', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Firma de las llamadas servidor-a-servidor de la tienda
    |--------------------------------------------------------------------------
    |
    | 29-sep-2026: POST /api/documents (y /create, /process) lo llama la tienda
    | sin firmar. Con true se exige la misma firma que el webhook order-paid
    | (X-Webhook-Timestamp + X-Webhook-Signature = HMAC-SHA256 de
    | "{timestamp}:{raw_body}" con DOCUMENTS_PRESTASHOP_WEBHOOK_SECRET).
    | Poner a true solo cuando la tienda firme. GET /verify y /order/{id}
    | exigen firma siempre (la tienda no los usa).
    |
    */
    'require_signed_server_requests' => (bool) env('DOCUMENTS_REQUIRE_SIGNED_SERVER_REQUESTS', false),

    /*
    |--------------------------------------------------------------------------
    | Disco privado de los ficheros de documentos
    |--------------------------------------------------------------------------
    |
    | 29-sep-2026: DNI, licencias y adjuntos de los expedientes (colecciones
    | media-library 'documents' y 'additional_attachments'). Root fuera de
    | public/: no hay URL /storage. La 'url' apunta a la ruta autenticada del
    | panel (DocumentMediaController), así $media->getUrl() sigue funcionando
    | para quien tenga can:view-documents-panel.
    |
    */
    'private_disk' => [
        'driver' => 'local',
        'root' => storage_path('app/documents_private'),
        'url' => rtrim((string) env('APP_URL', ''), '/').'/panel/documents/media',
        'visibility' => 'private',
        'permissions' => [
            'file' => ['public' => 0660, 'private' => 0660],
            'dir' => ['public' => 0770, 'private' => 0770],
        ],
        'throw' => false,
    ],

    // Minutos de validez de las URLs firmadas de ficheros (helpdesk / tienda).
    'signed_media_url_minutes' => (int) env('DOCUMENTS_SIGNED_MEDIA_URL_MINUTES', 120),

    /*
    |--------------------------------------------------------------------------
    | Estados pagados de Prestashop
    |--------------------------------------------------------------------------
    |
    | Lista de IDs de estados en Prestashop que se consideran "pagados".
    | Se utiliza para determinar cuándo enviar recordatorios de documentación.
    |
    */
    'paid_statuses' => array_values(array_map(
        'intval',
        array_filter(
            array_map('trim', explode(',', env('DOCUMENTS_PRESTASHOP_PAID_STATUS_IDS', '2'))),
            fn ($value) => $value !== ''
        )
    )),

    /*
    |--------------------------------------------------------------------------
    | Configuración de Navegación
    |--------------------------------------------------------------------------
    |
    | Elementos de navegación para el menú lateral del módulo de documentos.
    |
    */
    'navigation' => [
        'sidebar' => [
            'insert_after' => 'helpdesk',
            'section' => [
                'title' => 'Documentos',
                'permission' => 'documents.manage',
                'items' => [
                    [
                        'label' => 'Configuraciones',
                        'route' => 'settings.documents.configurations.global',
                        'permission' => 'documents.manage',
                    ],
                    [
                        'label' => 'Almacenamiento',
                        'route' => 'settings.documents.configurations.storage',
                        'permission' => 'documents.manage',
                    ],
                    [
                        'label' => 'Grupos de validación',
                        'route' => 'settings.documents.groups.index',
                        'permission' => 'documents.manage',
                    ],
                    [
                        'label' => 'Tipos de documento',
                        'route' => 'settings.documents.types.index',
                        'permission' => 'documents.manage',
                    ],
                    [
                        'label' => 'Condiciones de validación',
                        'route' => 'settings.documents.conditions.index',
                        'permission' => 'documents.manage',
                    ],
                    [
                        'label' => 'Políticas SLA',
                        'route' => 'settings.documents.sla-policies.index',
                        'permission' => 'documents.manage',
                    ],
                    [
                        'label' => 'Bloqueos de producto',
                        'route' => 'settings.documents.blockades.index',
                        'permission' => 'documents.manage',
                    ],
                ],
            ],
        ],
    ],
];
