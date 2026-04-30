<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración de Navegación
    |--------------------------------------------------------------------------
    |
    | Elementos de navegación para el menú lateral del módulo de Pages Chat.
    |
    */
    'navigation' => [
        'sidebar' => [
            'insert_after' => 'documents',
            'section' => [
                'title' => 'Pages Chat',
                'permission' => 'Chat.manage',
                'items' => [
                    [
                        'label' => 'Conversaciones',
                        'route' => 'chat.conversations.index',
                        'permission' => 'Chat.view-conversations',
                    ],
                    [
                        'label' => 'Mis conversaciones',
                        'route' => 'chat.conversations.mine',
                        'permission' => 'Chat.view-conversations',
                    ],
                    [
                        'label' => 'Sin asignar',
                        'route' => 'chat.conversations.unassigned',
                        'permission' => 'Chat.assign-conversations',
                    ],
                    [
                        'label' => 'Clientes',
                        'route' => 'chat.customers.index',
                        'permission' => 'Chat.manage-customers',
                    ],
                    [
                        'label' => 'Equipos',
                        'route' => 'settings.chat.teams.index',
                        'permission' => 'Chat.manage-teams',
                    ],
                    [
                        'label' => 'Etiquetas',
                        'route' => 'settings.chat.labels.index',
                        'permission' => 'Chat.manage-labels',
                    ],
                    [
                        'label' => 'Configuración',
                        'route' => 'settings.chat.configurations.global',
                        'permission' => 'Chat.manage-settings',
                    ],
                ],
            ],
        ],
    ],
];
