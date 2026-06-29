<?php

return [
    'module_name' => 'Roles & permissions',
    'breadcrumb' => 'Roles',

    'actions' => [
        'create' => 'Create',
        'edit' => 'Edit',
        'update' => 'Update',
        'delete' => 'Delete',
        'view' => 'View',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'back' => 'Back',
        'export' => 'Export',
        'search' => 'Search',
        'filter' => 'Filter',
        'reset' => 'Reset',
    ],

    'messages' => [
        'created' => 'Role created successfully.',
        'updated' => 'Role updated successfully.',
        'deleted' => 'Role deleted successfully.',
        'error' => 'An error occurred. Please try again.',
        'not_found' => 'Role not found.',
        'unauthorized' => 'You do not have permission to perform this action.',
    ],

    'roles' => [
        'title' => 'Role management',
        'create' => 'New role',
        'edit' => 'Edit role',
        'all' => 'All roles',
        'name' => 'Role name',
        'guard' => 'Guard',
        'total' => 'Total roles',
        'no_roles' => 'No roles available.',
        'protected' => 'This is a system role and cannot be deleted.',
    ],

    'permissions' => [
        'title' => 'Permissions',
        'assign' => 'Assign permissions',
        'all' => 'All permissions',
        'none' => 'No permissions',
        'select_all' => 'Select all',
        'deselect_all' => 'Deselect all',
        'updated' => 'Permissions updated successfully.',
        'group' => 'Group',
    ],

    'assign_users' => [
        'title' => 'Assign users',
        'add' => 'Add user',
        'remove' => 'Remove user',
        'assigned' => 'User assigned to role successfully.',
        'removed' => 'User removed from role successfully.',
        'search_user' => 'Search user...',
        'no_users' => 'No users assigned to this role.',
    ],

    'matrix' => [
        'title' => 'Permissions matrix',
        'description' => 'Comparative view of permissions by role.',
        'module' => 'Module',
    ],

    'compare' => [
        'title' => 'Compare roles',
        'select_roles' => 'Select the roles to compare.',
        'only_in' => 'Only in :role',
        'in_both' => 'In both roles',
    ],
];
