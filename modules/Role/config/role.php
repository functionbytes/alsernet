<?php

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
        'role' => \Spatie\Permission\Models\Role::class,
        'permission' => \Spatie\Permission\Models\Permission::class,
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
];
