<?php

return [
    'title' => 'Gestor de medios',
    'description' => 'Administrar archivos y carpetas',
    'search' => 'Buscar archivos...',
    'upload' => 'Subir archivo',
    'upload_folder' => 'Subir carpeta',
    'new_folder' => 'Nueva carpeta',
    'bulk_actions' => 'Acciones masivas',

    'actions' => [
        'rename' => 'Renombrar',
        'move' => 'Mover',
        'copy' => 'Copiar',
        'delete' => 'Eliminar',
        'restore' => 'Restaurar',
        'favorite_add' => 'Agregar a favoritos',
        'favorite_remove' => 'Quitar de favoritos',
        'preview' => 'Vista previa',
        'share' => 'Compartir',
        'download' => 'Descargar',
        'edit_image' => 'Editar imagen',
        'versions' => 'Historial de versiones',
        'access_logs' => 'Ver accesos',
        'set_expiration' => 'Establecer expiración',
    ],

    'views' => [
        'all_media' => 'Todos los archivos',
        'trash' => 'Papelera',
        'favorites' => 'Favoritos',
        'recent' => 'Recientes',
    ],

    'duplicates' => [
        'title' => 'Detector de duplicados',
        'mode_exact' => 'Hash exacto (idénticos)',
        'mode_similar' => 'Visualmente similares (pHash)',
        'no_duplicates' => 'No se encontraron duplicados.',
    ],

    'quota' => [
        'title' => 'Almacenamiento',
        'used' => ':used / :total',
        'exceeded' => 'Cuota excedida',
    ],

    'files' => [
        'title' => 'Archivos',
        'upload' => 'Subir archivo',
        'download' => 'Descargar',
        'delete' => 'Eliminar',
        'rename' => 'Renombrar',
        'move' => 'Mover',
        'copy' => 'Copiar',
        'preview' => 'Vista previa',
        'details' => 'Detalles',
        'size' => 'Tamaño',
        'type' => 'Tipo',
        'created_at' => 'Creado',
        'updated_at' => 'Actualizado',
        'no_files' => 'No se encontraron archivos',
    ],

    'folders' => [
        'title' => 'Carpetas',
        'create' => 'Nueva carpeta',
        'rename' => 'Renombrar carpeta',
        'delete' => 'Eliminar carpeta',
        'empty' => 'Carpeta vacía',
        'no_folders' => 'No se encontraron carpetas',
    ],

    'stats' => [
        'total_files' => 'Total de archivos',
        'total_size' => 'Tamaño total',
        'images' => 'Imágenes',
        'documents' => 'Documentos',
        'videos' => 'Videos',
        'other' => 'Otros',
    ],

    'errors' => [
        'upload_failed' => 'Error al subir el archivo.',
        'delete_failed' => 'No se pudo eliminar el archivo.',
        'not_found' => 'Archivo no encontrado.',
        'too_large' => 'El archivo supera el tamaño máximo permitido.',
        'invalid_type' => 'El tipo de archivo no está permitido.',
        'folder_not_empty' => 'No se puede eliminar una carpeta que no está vacía.',
        'quota_exceeded' => 'Cuota de almacenamiento excedida',
        'invalid_file' => 'Tipo de archivo no permitido',
    ],

    'success' => [
        'uploaded' => 'Archivo subido correctamente.',
        'deleted' => 'Archivo eliminado',
        'restored' => 'Archivo restaurado',
        'renamed' => 'Archivo renombrado correctamente.',
        'moved' => 'Archivo movido correctamente.',
        'copied' => 'Archivo copiado',
        'shared' => 'Enlace compartido copiado',
    ],

    'permissions' => [
        'manage' => 'Administrar medios',
        'upload' => 'Subir archivos',
        'delete' => 'Eliminar archivos',
        'view' => 'Ver medios',
    ],
];
