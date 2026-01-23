<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración de Navegación
    |--------------------------------------------------------------------------
    |
    | Elementos de navegación para el menú lateral del módulo de Helpdesk Chat.
    |
    */
    'navigation' => [
        'sidebar' => [
            'insert_after' => 'documents',
            'section' => [
                'title' => 'Helpdesk Chat',
                'permission' => 'helpdeskchat.manage',
                'items' => [
                    [
                        'label' => 'Conversaciones',
                        'route' => 'helpdeskchat.conversations.index',
                        'permission' => 'helpdeskchat.view-conversations',
                    ],
                    [
                        'label' => 'Mis conversaciones',
                        'route' => 'helpdeskchat.conversations.mine',
                        'permission' => 'helpdeskchat.view-conversations',
                    ],
                    [
                        'label' => 'Sin asignar',
                        'route' => 'helpdeskchat.conversations.unassigned',
                        'permission' => 'helpdeskchat.assign-conversations',
                    ],
                    [
                        'label' => 'Contactos',
                        'route' => 'helpdeskchat.contacts.index',
                        'permission' => 'helpdeskchat.manage-contacts',
                    ],
                    [
                        'label' => 'Clientes',
                        'route' => 'helpdeskchat.customers.index',
                        'permission' => 'helpdeskchat.manage-customers',
                    ],
                    [
                        'label' => 'Equipos',
                        'route' => 'helpdeskchat.teams.index',
                        'permission' => 'helpdeskchat.manage-teams',
                    ],
                    [
                        'label' => 'Etiquetas',
                        'route' => 'helpdeskchat.labels.index',
                        'permission' => 'helpdeskchat.manage-labels',
                    ],
                    [
                        'label' => 'Configuración',
                        'route' => 'settings.helpdeskchat.general',
                        'permission' => 'helpdeskchat.manage-settings',
                    ],
                ],
            ],
        ],
    ],
];
