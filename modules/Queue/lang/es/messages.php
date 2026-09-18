<?php

return [
    'module_name' => 'Colas de trabajo',
    'breadcrumb' => 'Colas',

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

    'failed_jobs' => [
        'title' => 'Trabajos fallidos',
        'all' => 'Todos los trabajos fallidos',
        'total' => 'Total fallidos',
        'id' => 'ID',
        'queue' => 'Cola',
        'payload' => 'Datos',
        'exception' => 'Excepción',
        'failed_at' => 'Falló el',
        'no_failed' => 'No hay trabajos fallidos.',
        'delete' => 'Eliminar trabajo',
        'delete_all' => 'Eliminar todos',
        'delete_success' => 'Trabajo eliminado correctamente.',
        'delete_all_success' => 'Todos los trabajos fallidos eliminados.',
    ],

    'pending_jobs' => [
        'title' => 'Trabajos pendientes',
        'all' => 'Todos los trabajos pendientes',
        'total' => 'Total pendientes',
        'queue' => 'Cola',
        'attempts' => 'Intentos',
        'reserved_at' => 'Reservado el',
        'available_at' => 'Disponible el',
        'created_at' => 'Creado el',
        'no_pending' => 'No hay trabajos pendientes.',
    ],

    'retry' => [
        'button' => 'Reintentar',
        'success' => 'Trabajo enviado para reintento correctamente.',
        'all' => 'Reintentar todos',
        'all_success' => 'Todos los trabajos enviados para reintento.',
        'not_found' => 'El trabajo no fue encontrado.',
    ],

    'horizon' => [
        'title' => 'Laravel Horizon',
        'status' => 'Estado de Horizon',
        'running' => 'En ejecución',
        'paused' => 'Pausado',
        'inactive' => 'Inactivo',
        'pause' => 'Pausar Horizon',
        'continue' => 'Continuar Horizon',
        'workers' => 'Trabajadores activos',
        'throughput' => 'Trabajos/min',
        'open_dashboard' => 'Abrir dashboard de Horizon',
    ],

    'queues' => [
        'default' => 'Por defecto',
        'emails' => 'Correos',
        'notifications' => 'Notificaciones',
        'exports' => 'Exportaciones',
        'size' => 'Tamaño de cola',
    ],
];
