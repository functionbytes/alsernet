<?php

return [
    'title' => 'Gestor de medios',
    'description' => 'Administrar archivos y carpetas',

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
    ],

    'success' => [
        'uploaded' => 'Archivo subido correctamente.',
        'deleted' => 'Archivo eliminado correctamente.',
        'renamed' => 'Archivo renombrado correctamente.',
        'moved' => 'Archivo movido correctamente.',
    ],

    'permissions' => [
        'manage' => 'Administrar medios',
        'upload' => 'Subir archivos',
        'delete' => 'Eliminar archivos',
        'view' => 'Ver medios',
    ],
];
