<?php

return [
    'module_name' => 'Notificaciones',
    'breadcrumb' => 'Notificaciones',

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
        'created' => 'Notificación creada correctamente.',
        'updated' => 'Notificación actualizada correctamente.',
        'deleted' => 'Notificación eliminada correctamente.',
        'error' => 'Ha ocurrido un error. Por favor, inténtalo de nuevo.',
        'not_found' => 'Notificación no encontrada.',
        'unauthorized' => 'No tienes permiso para realizar esta acción.',
    ],

    'notifications' => [
        'title' => 'Notificaciones',
        'all' => 'Todas las notificaciones',
        'unread' => 'Sin leer',
        'read' => 'Leídas',
        'empty' => 'No tienes notificaciones.',
        'empty_unread' => 'No tienes notificaciones sin leer.',
        'count' => ':count notificación|:count notificaciones',
    ],

    'mark_as_read' => [
        'button' => 'Marcar como leída',
        'success' => 'Notificación marcada como leída.',
    ],

    'mark_all_read' => [
        'button' => 'Marcar todas como leídas',
        'success' => 'Todas las notificaciones marcadas como leídas.',
    ],

    'preferences' => [
        'title' => 'Preferencias de notificaciones',
        'email' => 'Notificaciones por correo',
        'push' => 'Notificaciones push',
        'database' => 'Notificaciones en el sistema',
        'updated' => 'Preferencias actualizadas correctamente.',
        'channel' => 'Canal',
        'enabled' => 'Activado',
        'disabled' => 'Desactivado',
    ],

    'push_tokens' => [
        'title' => 'Tokens push',
        'register' => 'Registrar dispositivo',
        'revoke' => 'Revocar token',
        'revoked' => 'Token revocado correctamente.',
        'device' => 'Dispositivo',
        'last_used' => 'Último uso',
    ],

    'types' => [
        'system' => 'Sistema',
        'alert' => 'Alerta',
        'info' => 'Información',
        'success' => 'Éxito',
        'warning' => 'Advertencia',
    ],
];
