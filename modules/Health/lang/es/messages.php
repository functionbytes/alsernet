<?php

return [
    'module_name' => 'Estado del sistema',
    'breadcrumb' => 'Estado del sistema',

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

    'health_status' => [
        'title' => 'Estado de salud',
        'overall' => 'Estado general',
        'healthy' => 'Saludable',
        'unhealthy' => 'No saludable',
        'warning' => 'Advertencia',
        'check' => 'Verificar ahora',
        'last_checked' => 'Última verificación',
        'services' => 'Servicios monitoreados',
        'all_ok' => 'Todos los servicios funcionan correctamente.',
        'issues_found' => 'Se encontraron problemas en :count servicio(s).',
    ],

    'alerts' => [
        'title' => 'Alertas',
        'active' => 'Alertas activas',
        'resolved' => 'Alertas resueltas',
        'no_alerts' => 'No hay alertas activas.',
        'resolve' => 'Resolver alerta',
        'resolved_success' => 'Alerta resuelta correctamente.',
        'sent_to' => 'Alerta enviada a :email.',
    ],

    'thresholds' => [
        'title' => 'Umbrales de alerta',
        'cpu' => 'Umbral de CPU (%)',
        'memory' => 'Umbral de memoria (%)',
        'disk' => 'Umbral de disco (%)',
        'response_time' => 'Tiempo de respuesta máximo (ms)',
        'updated' => 'Umbrales actualizados correctamente.',
    ],

    'history' => [
        'title' => 'Historial',
        'period' => 'Período',
        'uptime' => 'Tiempo activo',
        'incidents' => 'Incidentes',
        'no_history' => 'No hay historial disponible.',
        'export' => 'Exportar historial',
    ],

    'checks' => [
        'database' => 'Base de datos',
        'cache' => 'Caché',
        'queue' => 'Cola de trabajos',
        'storage' => 'Almacenamiento',
        'mail' => 'Correo',
        'redis' => 'Redis',
    ],
];
