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
];
