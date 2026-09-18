<?php

return [
    'module_name' => 'Notifications',
    'breadcrumb' => 'Notifications',

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
        'created' => 'Notification created successfully.',
        'updated' => 'Notification updated successfully.',
        'deleted' => 'Notification deleted successfully.',
        'error' => 'An error occurred. Please try again.',
        'not_found' => 'Notification not found.',
        'unauthorized' => 'You do not have permission to perform this action.',
    ],

    'notifications' => [
        'title' => 'Notifications',
        'all' => 'All notifications',
        'unread' => 'Unread',
        'read' => 'Read',
        'empty' => 'You have no notifications.',
        'empty_unread' => 'You have no unread notifications.',
        'count' => ':count notification|:count notifications',
    ],

    'mark_as_read' => [
        'button' => 'Mark as read',
        'success' => 'Notification marked as read.',
    ],

    'mark_all_read' => [
        'button' => 'Mark all as read',
        'success' => 'All notifications marked as read.',
    ],

    'preferences' => [
        'title' => 'Notification preferences',
        'email' => 'Email notifications',
        'push' => 'Push notifications',
        'database' => 'In-app notifications',
        'updated' => 'Preferences updated successfully.',
        'channel' => 'Channel',
        'enabled' => 'Enabled',
        'disabled' => 'Disabled',
    ],

    'push_tokens' => [
        'title' => 'Push tokens',
        'register' => 'Register device',
        'revoke' => 'Revoke token',
        'revoked' => 'Token revoked successfully.',
        'device' => 'Device',
        'last_used' => 'Last used',
    ],

    'types' => [
        'system' => 'System',
        'alert' => 'Alert',
        'info' => 'Info',
        'success' => 'Success',
        'warning' => 'Warning',
    ],
];
