<?php

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return [
    /*
    |--------------------------------------------------------------------------
    | Role and Permission Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file is for the Role management module.
    |
    */

    'model' => [
        'role' => Role::class,
        'permission' => Permission::class,
    ],

    'guards' => [
        'web' => 'users',
        'api' => 'api',
    ],

    'table_names' => [
        'roles' => 'roles',
        'permissions' => 'permissions',
        'model_has_permissions' => 'model_has_permissions',
        'model_has_roles' => 'model_has_roles',
        'role_has_permissions' => 'role_has_permissions',
    ],

    /*
    |--------------------------------------------------------------------------
    | Protected Roles
    |--------------------------------------------------------------------------
    |
    | These system roles cannot be modified or deleted through the UI.
    |
    */
    'protected_roles' => ['super-settings', 'customer'],

    /*
    |--------------------------------------------------------------------------
    | Privileged Roles (29-sep-2026)
    |--------------------------------------------------------------------------
    |
    | Solo un super-admin puede asignar/quitar estos roles, cambiar sus
    | permisos, o editar/borrar/impersonar a un usuario que los tenga. Ver
    | Modules\Role\Services\PrivilegeGuard. Además, quien no es super-admin
    | solo puede conceder permisos (o roles) que él mismo ya tiene.
    |
    */
    'privileged_roles' => ['super-admin', 'super-settings', 'settings'],
];
