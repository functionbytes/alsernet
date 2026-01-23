<?php

/**
 * Configuración de Permisos por Módulo
 *
 * Estructura: {module}.{action}.{scope} o {module}.{category}.{action}
 * - module: Identificador del módulo (documents, users, warehouse, etc.)
 * - action: Acción a realizar (view, create, update, delete, approve, manage)
 * - scope: Alcance de la acción (all, own, team)
 * - category: Subcategoría de permisos (files, types, settings, etc.)
 *
 * Esta configuración es procesada por OrganizedPermissionsSeeder y SyncPermissionsCommand
 * para generar y sincronizar permisos en la base de datos.
 *
 * Estructura inspirada en document_permissions system:
 * - Permisos organizados por módulo y tipo de acción
 * - Soporte para categorías y sub-permisos
 * - Scopes para control granular de acceso (all, own, team)
 */

return [
    // ================================================================
    // MÓDULO: DOCUMENTS (Documentos)
    // ================================================================
    // Estructura basada en DocumentPermissionsSeeder y document_permissions table
    'documents' => [
        'name' => 'Documentos',
        'description' => 'Gestión de documentos del sistema',
        'permissions' => [
            'view' => [
                'all' => 'Ver todos los documentos',
                'own' => 'Ver solo sus documentos',
            ],
            'create' => [
                'own' => 'Crear documentos',
            ],
            'update' => [
                'own' => 'Editar sus documentos',
                'all' => 'Editar cualquier documento',
            ],
            'delete' => [
                'own' => 'Eliminar sus documentos',
                'all' => 'Eliminar cualquier documento',
            ],
            'manage' => [
                'all' => 'Gestionar documentos completamente',
            ],
            'approve' => [
                'all' => 'Aprobar documentos',
            ],
            'reject' => [
                'all' => 'Rechazar documentos',
            ],
            'export' => [
                'own' => 'Exportar sus documentos',
                'all' => 'Exportar todos los documentos',
            ],
            'files' => [
                'upload' => 'Cargar archivos en documentos',
                'download' => 'Descargar archivos de documentos',
                'delete' => 'Eliminar archivos de documentos',
            ],
            'types' => [
                'view' => 'Ver tipos de documento',
                'create' => 'Crear tipos de documento',
                'update' => 'Editar tipos de documento',
                'delete' => 'Eliminar tipos de documento',
                'manage' => 'Gestionar tipos de documento',
            ],
            'settings' => [
                'view' => 'Ver configuración de documentos',
                'update' => 'Editar configuración de documentos',
                'manage' => 'Gestionar configuración de documentos',
            ],
        ],
    ],

    // ================================================================
    // MÓDULO: USERS (Usuarios)
    // ================================================================
    'users' => [
        'name' => 'Usuarios',
        'description' => 'Gestión de usuarios del sistema',
        'permissions' => [
            'view' => [
                'all' => 'Ver lista de usuarios',
            ],
            'show' => [
                'all' => 'Ver detalles de usuario',
            ],
            'create' => [
                'all' => 'Crear usuarios',
            ],
            'update' => [
                'own' => 'Editar su propio perfil',
                'all' => 'Editar cualquier usuario',
            ],
            'delete' => [
                'all' => 'Eliminar usuarios',
            ],
            'export' => [
                'all' => 'Exportar listado de usuarios',
            ],
            'manage' => [
                'all' => 'Gestionar usuarios completamente',
            ],
            'roles' => [
                'view' => 'Ver roles asignados a usuarios',
                'assign' => 'Asignar roles a usuarios',
                'remove' => 'Remover roles de usuarios',
            ],
            'activate' => [
                'all' => 'Activar/Desactivar usuarios',
            ],
            'password' => [
                'reset' => 'Restablecer contraseña de usuarios',
            ],
        ],
    ],

    // ================================================================
    // MÓDULO: ROLES & PERMISSIONS (Roles y Permisos)
    // ================================================================
    // Estructura expandida con granularidad de operaciones por rol
    'roles' => [
        'name' => 'Roles y Permisos',
        'description' => 'Gestión de roles, permisos y acceso a módulos',
        'permissions' => [
            'view' => [
                'all' => 'Ver lista de roles y permisos',
            ],
            'show' => [
                'all' => 'Ver detalles de un rol específico',
            ],
            'create' => [
                'all' => 'Crear nuevos roles',
            ],
            'update' => [
                'all' => 'Editar información de roles',
            ],
            'delete' => [
                'all' => 'Eliminar roles',
            ],
            'export' => [
                'all' => 'Exportar listado de roles',
            ],
            'manage' => [
                'all' => 'Gestionar roles y permisos completamente',
            ],
            'permissions' => [
                'view' => 'Ver matriz de permisos',
                'assign' => 'Asignar permisos a roles',
                'remove' => 'Remover permisos de roles',
                'manage' => 'Gestionar permisos completamente',
            ],
            'modules' => [
                'view' => 'Ver visibilidad de módulos',
                'assign' => 'Asignar módulos a roles',
                'remove' => 'Remover módulos de roles',
                'manage' => 'Gestionar módulos de roles completamente',
            ],
            'users' => [
                'view' => 'Ver usuarios asignados a roles',
                'assign' => 'Asignar usuarios a roles',
                'remove' => 'Remover usuarios de roles',
            ],
        ],
    ],

    // ================================================================
    // MÓDULO: WAREHOUSE (Almacén)
    // ================================================================
    'warehouse' => [
        'name' => 'Almacén',
        'description' => 'Gestión de inventario y almacén',
        'permissions' => [
            'view' => [
                'all' => 'Ver almacén y estadísticas',
            ],
            'manage' => [
                'all' => 'Gestionar almacén completamente',
            ],
            'inventory' => [
                'view' => 'Ver inventario',
                'update' => 'Actualizar inventario',
                'audit' => 'Realizar auditoría de inventario',
            ],
            'locations' => [
                'view' => 'Ver ubicaciones de almacén',
                'create' => 'Crear ubicaciones',
                'update' => 'Editar ubicaciones',
                'delete' => 'Eliminar ubicaciones',
            ],
            'movements' => [
                'view' => 'Ver movimientos de inventario',
                'create' => 'Registrar movimientos',
                'export' => 'Exportar historial de movimientos',
            ],
            'reports' => [
                'view' => 'Ver reportes de almacén',
                'export' => 'Exportar reportes',
            ],
        ],
    ],

    // ================================================================
    // MÓDULO: MAILERS (Correos)
    // ================================================================
    'mailers' => [
        'name' => 'Gestión de Correos',
        'description' => 'Configuración, plantillas y envío de correos',
        'permissions' => [
            'view' => [
                'all' => 'Ver módulo de correos',
            ],
            'templates' => [
                'view' => 'Ver plantillas de correo',
                'create' => 'Crear plantillas de correo',
                'update' => 'Editar plantillas de correo',
                'delete' => 'Eliminar plantillas de correo',
                'test' => 'Enviar email de prueba',
            ],
            'endpoints' => [
                'view' => 'Ver puntos de envío',
                'create' => 'Crear puntos de envío',
                'update' => 'Editar puntos de envío',
                'delete' => 'Eliminar puntos de envío',
                'test' => 'Probar conexión de endpoints',
            ],
            'components' => [
                'view' => 'Ver componentes de email',
                'manage' => 'Gestionar componentes de email',
            ],
            'history' => [
                'view' => 'Ver historial de correos enviados',
                'export' => 'Exportar historial de correos',
            ],
            'manage' => [
                'all' => 'Gestionar correos completamente',
            ],
        ],
    ],

    // ================================================================
    // MÓDULO: SETTINGS (Configuraciones)
    // ================================================================
    'settings' => [
        'name' => 'Configuraciones del Sistema',
        'description' => 'Configuraciones generales del sistema',
        'permissions' => [
            'view' => [
                'all' => 'Ver todas las configuraciones',
            ],
            'system' => [
                'view' => 'Ver configuración del sistema',
                'update' => 'Editar configuración del sistema',
            ],
            'email' => [
                'view' => 'Ver configuración de correo',
                'update' => 'Editar configuración de correo',
            ],
            'security' => [
                'view' => 'Ver configuración de seguridad',
                'update' => 'Editar configuración de seguridad',
            ],
            'backup' => [
                'view' => 'Ver configuración de copias de seguridad',
                'create' => 'Crear copias de seguridad',
                'restore' => 'Restaurar copias de seguridad',
            ],
            'logs' => [
                'view' => 'Ver logs del sistema',
                'delete' => 'Limpiar logs',
            ],
        ],
    ],

    // ================================================================
    // MÓDULO: MEDIA (Gestor de Medios)
    // ================================================================
    'media' => [
        'name' => 'Gestor de Medios',
        'description' => 'Gestión de archivos e imágenes',
        'permissions' => [
            'view' => [
                'all' => 'Ver galería de medios',
            ],
            'upload' => [
                'own' => 'Subir archivos propios',
                'all' => 'Subir archivos para otros',
            ],
            'delete' => [
                'own' => 'Eliminar sus archivos',
                'all' => 'Eliminar archivos de otros',
            ],
            'folders' => [
                'view' => 'Ver carpetas de medios',
                'create' => 'Crear carpetas',
                'update' => 'Editar carpetas',
                'delete' => 'Eliminar carpetas',
            ],
            'manage' => [
                'all' => 'Gestionar medios completamente',
            ],
        ],
    ],

    // ================================================================
    // PERMISOS GLOBALES DE MÓDULOS
    // ================================================================
    'modules' => [
        'name' => 'Visibilidad de Módulos',
        'description' => 'Control de qué módulos ven los usuarios',
        'permissions' => [
            'view' => [
                'activity' => 'Ver módulo de Actividad',
                'analytics' => 'Ver módulo de Analytics',
                'auth' => 'Ver módulo de Seguridad',
                'backups' => 'Ver módulo de Copias de Seguridad',
                'campaigns' => 'Ver módulo de Campañas',
                'dashboard' => 'Ver módulo de Dashboard',
                'documents' => 'Ver módulo de Documentos',
                'events' => 'Ver módulo de Eventos',
                'helpdesk' => 'Ver módulo de Helpdesk',
                'mailers' => 'Ver módulo de Correos',
                'media' => 'Ver módulo de Medios',
                'notifications' => 'Ver módulo de Notificaciones',
                'roles' => 'Ver módulo de Roles y Permisos',
                'settings' => 'Ver módulo de Configuraciones',
                'subscribers' => 'Ver módulo de Suscriptores',
                'suppliers' => 'Ver módulo de Proveedores',
                'users' => 'Ver módulo de Usuarios',
                'warehouse' => 'Ver módulo de Almacén',
                'webhooks' => 'Ver módulo de Webhooks',
            ],
        ],
    ],
];
