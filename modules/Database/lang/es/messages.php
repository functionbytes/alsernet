<?php

return [
    'module_name' => 'Base de datos',
    'breadcrumb' => 'Base de datos',

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

    'cleanup' => [
        'title' => 'Limpieza de base de datos',
        'button' => 'Ejecutar limpieza',
        'success' => 'Limpieza ejecutada correctamente.',
        'tables_cleaned' => ':count tabla(s) limpiadas.',
        'confirm' => '¿Estás seguro? Esta acción eliminará datos irreversiblemente.',
        'older_than' => 'Registros anteriores a',
        'days' => 'días',
    ],

    'truncate' => [
        'title' => 'Vaciar tablas',
        'button' => 'Vaciar tabla',
        'success' => 'Tabla vaciada correctamente.',
        'confirm' => '¿Estás seguro de que deseas vaciar la tabla ":table"?',
        'select_table' => 'Selecciona una tabla',
        'warning' => 'Esta acción eliminará todos los registros de la tabla.',
    ],

    'settings' => [
        'title' => 'Configuración de base de datos',
        'connection' => 'Conexión',
        'host' => 'Host',
        'port' => 'Puerto',
        'database' => 'Nombre de la base de datos',
        'username' => 'Usuario',
        'password' => 'Contraseña',
        'charset' => 'Charset',
        'collation' => 'Collation',
        'updated' => 'Configuración actualizada correctamente.',
        'test_connection' => 'Probar conexión',
        'connection_ok' => 'Conexión exitosa.',
        'connection_error' => 'Error de conexión: :message',
    ],

    'backup' => [
        'title' => 'Respaldo de base de datos',
        'create' => 'Crear respaldo',
        'created' => 'Respaldo creado correctamente.',
        'download' => 'Descargar respaldo',
        'delete' => 'Eliminar respaldo',
        'deleted' => 'Respaldo eliminado correctamente.',
        'restore' => 'Restaurar',
        'restored' => 'Base de datos restaurada correctamente.',
        'no_backups' => 'No hay respaldos disponibles.',
        'size' => 'Tamaño',
        'created_at' => 'Fecha de creación',
    ],

    'stats' => [
        'title' => 'Estadísticas',
        'total_tables' => 'Total de tablas',
        'total_size' => 'Tamaño total',
        'total_records' => 'Total de registros',
        'table' => 'Tabla',
        'rows' => 'Filas',
        'size' => 'Tamaño',
    ],
];
