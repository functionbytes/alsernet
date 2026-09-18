<?php

return [
    'module_name' => 'Users',
    'breadcrumb' => 'Users',

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
        'created' => 'User created successfully.',
        'updated' => 'User updated successfully.',
        'deleted' => 'User deleted successfully.',
        'error' => 'An error occurred. Please try again.',
        'not_found' => 'User not found.',
        'unauthorized' => 'You do not have permission to perform this action.',
    ],

    'users' => [
        'title' => 'User management',
        'create' => 'New user',
        'edit' => 'Edit user',
        'all' => 'All users',
        'active' => 'Active users',
        'inactive' => 'Inactive users',
        'total' => 'Total users',
        'new_this_month' => 'New this month',
    ],

    'profile' => [
        'title' => 'Profile',
        'edit' => 'Edit profile',
        'updated' => 'Profile updated successfully.',
        'name' => 'Name',
        'email' => 'Email address',
        'phone' => 'Phone',
        'bio' => 'Bio',
    ],

    'avatar' => [
        'title' => 'Profile picture',
        'upload' => 'Upload photo',
        'remove' => 'Remove photo',
        'updated' => 'Profile picture updated successfully.',
        'removed' => 'Profile picture removed successfully.',
        'too_large' => 'Image cannot exceed 2MB.',
        'invalid_type' => 'Only JPG, PNG, or GIF images are allowed.',
    ],

    'impersonate' => [
        'title' => 'Impersonate user',
        'start' => 'Impersonate',
        'stop' => 'Stop impersonating',
        'active' => 'You are impersonating :name.',
        'success' => 'You are now acting as :name.',
        'ended' => 'Impersonation ended.',
    ],

    'bulk_action' => [
        'activate' => 'Activate selected',
        'deactivate' => 'Deactivate selected',
        'delete' => 'Delete selected',
        'success' => 'Action applied to :count users.',
    ],

    'export_csv' => [
        'title' => 'Export users',
        'success' => 'Export started successfully.',
        'filename' => 'users',
    ],

    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'suspended' => 'Suspended',
    ],
];
