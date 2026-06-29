<?php

/**
 * Analytics Module Permissions Configuration
 *
 * Structure: {module}.{action}.{scope}
 * - module: analytics
 * - action: view, manage, settings, export
 * - scope: all, own
 */

return [
    // ================================================================
    // MÓDULO: ANALYTICS (Analíticas)
    // ================================================================
    'analytics' => [
        'name' => 'Analíticas',
        'description' => 'Gestión de analíticas y estadísticas del sitio web',
        'permissions' => [
            'view' => [
                'all' => 'Ver dashboard de analíticas',
            ],
            'dashboard' => [
                'view' => 'Ver panel de analíticas',
                'export' => 'Exportar datos del dashboard',
            ],
            'widgets' => [
                'view' => 'Ver widgets de analíticas',
                'general' => 'Ver estadísticas generales',
                'top_pages' => 'Ver páginas más visitadas',
                'top_browsers' => 'Ver navegadores principales',
                'top_referrers' => 'Ver fuentes de tráfico',
                'manage' => 'Gestionar widgets',
            ],
            'reports' => [
                'view' => 'Ver reportes de analíticas',
                'create' => 'Crear reportes personalizados',
                'export' => 'Exportar reportes',
                'schedule' => 'Programar reportes automáticos',
            ],
            'settings' => [
                'view' => 'Ver configuración de analíticas',
                'update' => 'Editar configuración de analíticas',
                'manage' => 'Gestionar configuración completamente',
            ],
            'data' => [
                'view' => 'Ver datos de analíticas',
                'export' => 'Exportar datos de analíticas',
                'clear_cache' => 'Limpiar caché de analíticas',
            ],
            'manage' => [
                'all' => 'Gestionar analíticas completamente',
            ],
        ],
    ],
];
