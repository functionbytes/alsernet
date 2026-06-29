<?php

return [
    'module_name' => 'Roles y permisos',
    'breadcrumb' => 'Roles',

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
        'created' => 'Rol creado correctamente.',
        'updated' => 'Rol actualizado correctamente.',
        'deleted' => 'Rol eliminado correctamente.',
        'error' => 'Ha ocurrido un error. Por favor, inténtalo de nuevo.',
        'not_found' => 'Rol no encontrado.',
        'unauthorized' => 'No tienes permiso para realizar esta acción.',
    ],

    'roles' => [
        'title' => 'Gestión de roles',
        'create' => 'Nuevo rol',
        'edit' => 'Editar rol',
        'all' => 'Todos los roles',
        'name' => 'Nombre del rol',
        'guard' => 'Guard',
        'total' => 'Total roles',
        'no_roles' => 'No hay roles disponibles.',
        'protected' => 'Este rol es del sistema y no puede eliminarse.',
    ],

    'permissions' => [
        'title' => 'Permisos',
        'assign' => 'Asignar permisos',
        'all' => 'Todos los permisos',
        'none' => 'Ningún permiso',
        'select_all' => 'Seleccionar todos',
        'deselect_all' => 'Deseleccionar todos',
        'updated' => 'Permisos actualizados correctamente.',
        'group' => 'Grupo',
    ],

    'assign_users' => [
        'title' => 'Asignar usuarios',
        'add' => 'Agregar usuario',
        'remove' => 'Remover usuario',
        'assigned' => 'Usuario asignado al rol correctamente.',
        'removed' => 'Usuario removido del rol correctamente.',
        'search_user' => 'Buscar usuario...',
        'no_users' => 'No hay usuarios asignados a este rol.',
    ],

    'matrix' => [
        'title' => 'Matriz de permisos',
        'description' => 'Vista comparativa de permisos por rol.',
        'module' => 'Módulo',
    ],

    'compare' => [
        'title' => 'Comparar roles',
        'select_roles' => 'Selecciona los roles a comparar.',
        'only_in' => 'Solo en :role',
        'in_both' => 'En ambos roles',
    ],
];
