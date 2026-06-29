<?php

return [
    'module_name' => 'Caché',
    'breadcrumb' => 'Caché',

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

    'cache_settings' => [
        'title' => 'Configuración de caché',
        'driver' => 'Driver de caché',
        'prefix' => 'Prefijo de caché',
        'default_ttl' => 'TTL por defecto (minutos)',
        'updated' => 'Configuración de caché actualizada correctamente.',
    ],

    'flush_cache' => [
        'title' => 'Vaciar caché',
        'button' => 'Vaciar todo el caché',
        'success' => 'Caché vaciado correctamente.',
        'confirm' => '¿Estás seguro de que deseas vaciar todo el caché?',
        'by_tag' => 'Vaciar por etiqueta',
        'tag' => 'Etiqueta',
        'tag_flushed' => 'Caché de etiqueta ":tag" vaciado correctamente.',
        'by_key' => 'Vaciar por clave',
        'key' => 'Clave',
        'key_flushed' => 'Clave ":key" eliminada del caché.',
        'key_not_found' => 'La clave ":key" no existe en el caché.',
    ],

    'redis_stats' => [
        'title' => 'Estadísticas de Redis',
        'version' => 'Versión de Redis',
        'uptime' => 'Tiempo activo',
        'connected_clients' => 'Clientes conectados',
        'used_memory' => 'Memoria usada',
        'total_keys' => 'Total de claves',
        'hits' => 'Aciertos',
        'misses' => 'Fallos',
        'hit_rate' => 'Tasa de aciertos',
        'not_available' => 'Redis no está disponible.',
    ],

    'ttl' => [
        'title' => 'Tiempo de vida (TTL)',
        'seconds' => 'segundos',
        'minutes' => 'minutos',
        'hours' => 'horas',
        'days' => 'días',
        'forever' => 'Permanente',
        'expired' => 'Expirada',
        'expires_in' => 'Expira en :time',
    ],

    'keys' => [
        'title' => 'Claves en caché',
        'key' => 'Clave',
        'value' => 'Valor',
        'expires' => 'Expira',
        'no_keys' => 'No hay claves en caché.',
    ],
];
