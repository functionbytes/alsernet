<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Campaign Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Campaign Management module.
    |
    */

    'navigation' => [
        'sidebar' => [
            'insert_after' => 'helpdesk',
            'section' => [
                'title' => 'Campañas',
                'permission' => 'campaigns.manage',
                'items' => [
                    [
                        'label' => 'Campañas',
                        'route' => 'manager.campaigns.index',
                        'permission' => 'campaigns.manage',
                    ],
                    [
                        'label' => 'Automatizaciones',
                        'route' => 'manager.campaigns.automations.index',
                        'permission' => 'campaigns.manage',
                    ],
                    [
                        'label' => 'Plantillas',
                        'route' => 'manager.campaigns.templates.index',
                        'permission' => 'campaigns.manage',
                    ],
                    [
                        'label' => 'Segmentos',
                        'route' => 'manager.campaigns.segments.index',
                        'permission' => 'campaigns.manage',
                    ],
                    [
                        'label' => 'Suscriptores',
                        'route' => 'manager.campaigns.subscribers.index',
                        'permission' => 'campaigns.manage',
                    ],
                    [
                        'label' => 'Listas de correo',
                        'route' => 'manager.campaigns.maillists.index',
                        'permission' => 'campaigns.manage',
                    ],
                ],
            ],
        ],
    ],
];
