<?php

return [
    'module_name' => 'Sistema',
    'breadcrumb' => 'Sistema',

    'actions' => [
        'create' => 'Crear',
        'edit' => 'Editar',
        'update' => 'Actualizar',
        'delete' => 'Eliminar',
        'view' => 'Ver',
        'save' => 'Guardar',
        'cancel' => 'Cancelar',
        'back' => 'Volver',
        'export' => 'Exportar',
        'search' => 'Buscar',
        'filter' => 'Filtrar',
        'reset' => 'Restablecer',
    ],

    'messages' => [
        'created' => 'Creado correctamente.',
        'updated' => 'Actualizado correctamente.',
        'deleted' => 'Eliminado correctamente.',
        'error' => 'Ha ocurrido un error. Por favor, inténtalo de nuevo.',
        'not_found' => 'No encontrado.',
        'unauthorized' => 'No tienes permiso para realizar esta acción.',
    ],

    'cache_clear' => [
        'title' => 'Limpiar caché',
        'button' => 'Limpiar caché',
        'success' => 'Caché limpiado correctamente.',
        'config' => 'Limpiar caché de configuración',
        'routes' => 'Limpiar caché de rutas',
        'views' => 'Limpiar caché de vistas',
        'all' => 'Limpiar todo el caché',
        'config_cleared' => 'Caché de configuración limpiado.',
        'routes_cleared' => 'Caché de rutas limpiado.',
        'views_cleared' => 'Caché de vistas limpiado.',
    ],

    'supervisor' => [
        'title' => 'Supervisor',
        'status' => 'Estado de Supervisor',
        'running' => 'En ejecución',
        'stopped' => 'Detenido',
        'restart' => 'Reiniciar Supervisor',
        'restarted' => 'Supervisor reiniciado correctamente.',
        'not_installed' => 'Supervisor no está instalado.',
    ],

    'maintenance' => [
        'title' => 'Modo mantenimiento',
        'enable' => 'Activar mantenimiento',
        'disable' => 'Desactivar mantenimiento',
        'enabled' => 'Modo mantenimiento activado.',
        'disabled' => 'Modo mantenimiento desactivado.',
        'active' => 'El sistema está en mantenimiento.',
        'message' => 'Mensaje de mantenimiento',
        'retry_after' => 'Reintentar después (segundos)',
    ],

    'queue' => [
        'title' => 'Colas',
        'status' => 'Estado de las colas',
        'workers' => 'Trabajadores activos',
        'pending' => 'Trabajos pendientes',
        'failed' => 'Trabajos fallidos',
        'restart' => 'Reiniciar workers',
        'restarted' => 'Workers reiniciados correctamente.',
    ],

    'system_info' => [
        'title' => 'Información del sistema',
        'php_version' => 'Versión de PHP',
        'laravel_version' => 'Versión de Laravel',
        'server' => 'Servidor',
        'os' => 'Sistema operativo',
        'memory_limit' => 'Límite de memoria',
        'max_upload' => 'Tamaño máximo de subida',
        'environment' => 'Entorno',
        'debug_mode' => 'Modo depuración',
        'timezone' => 'Zona horaria',
        'locale' => 'Idioma',
    ],
];
