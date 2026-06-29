<?php

return [
    'module_name' => 'Usuarios',
    'breadcrumb' => 'Usuarios',

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
        'created' => 'Usuario creado correctamente.',
        'updated' => 'Usuario actualizado correctamente.',
        'deleted' => 'Usuario eliminado correctamente.',
        'error' => 'Ha ocurrido un error. Por favor, inténtalo de nuevo.',
        'not_found' => 'Usuario no encontrado.',
        'unauthorized' => 'No tienes permiso para realizar esta acción.',
    ],

    'users' => [
        'title' => 'Gestión de usuarios',
        'create' => 'Nuevo usuario',
        'edit' => 'Editar usuario',
        'all' => 'Todos los usuarios',
        'active' => 'Usuarios activos',
        'inactive' => 'Usuarios inactivos',
        'total' => 'Total usuarios',
        'new_this_month' => 'Nuevos este mes',
    ],

    'profile' => [
        'title' => 'Perfil',
        'edit' => 'Editar perfil',
        'updated' => 'Perfil actualizado correctamente.',
        'name' => 'Nombre',
        'email' => 'Correo electrónico',
        'phone' => 'Teléfono',
        'bio' => 'Biografía',
    ],

    'avatar' => [
        'title' => 'Foto de perfil',
        'upload' => 'Subir foto',
        'remove' => 'Eliminar foto',
        'updated' => 'Foto de perfil actualizada correctamente.',
        'removed' => 'Foto de perfil eliminada correctamente.',
        'too_large' => 'La imagen no puede superar los 2MB.',
        'invalid_type' => 'Solo se permiten imágenes JPG, PNG o GIF.',
    ],

    'impersonate' => [
        'title' => 'Suplantar usuario',
        'start' => 'Suplantar',
        'stop' => 'Dejar de suplantar',
        'active' => 'Estás suplantando a :name.',
        'success' => 'Ahora estás actuando como :name.',
        'ended' => 'Suplantación finalizada.',
    ],

    'bulk_action' => [
        'activate' => 'Activar seleccionados',
        'deactivate' => 'Desactivar seleccionados',
        'delete' => 'Eliminar seleccionados',
        'success' => 'Acción aplicada a :count usuarios.',
    ],

    'export_csv' => [
        'title' => 'Exportar usuarios',
        'success' => 'Exportación iniciada correctamente.',
        'filename' => 'usuarios',
    ],

    'status' => [
        'active' => 'Activo',
        'inactive' => 'Inactivo',
        'suspended' => 'Suspendido',
    ],
];
