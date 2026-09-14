<?php

return [
    'module_name' => 'System',
    'breadcrumb' => 'System',

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
        'created' => 'Created successfully.',
        'updated' => 'Updated successfully.',
        'deleted' => 'Deleted successfully.',
        'error' => 'An error occurred. Please try again.',
        'not_found' => 'Not found.',
        'unauthorized' => 'You do not have permission to perform this action.',
    ],

    'cache_clear' => [
        'title' => 'Clear cache',
        'button' => 'Clear cache',
        'success' => 'Cache cleared successfully.',
        'config' => 'Clear config cache',
        'routes' => 'Clear routes cache',
        'views' => 'Clear views cache',
        'all' => 'Clear all cache',
        'config_cleared' => 'Config cache cleared.',
        'routes_cleared' => 'Routes cache cleared.',
        'views_cleared' => 'Views cache cleared.',
    ],

    'supervisor' => [
        'title' => 'Supervisor',
        'status' => 'Supervisor status',
        'running' => 'Running',
        'stopped' => 'Stopped',
        'restart' => 'Restart Supervisor',
        'restarted' => 'Supervisor restarted successfully.',
        'not_installed' => 'Supervisor is not installed.',
    ],

    'maintenance' => [
        'title' => 'Maintenance mode',
        'enable' => 'Enable maintenance',
        'disable' => 'Disable maintenance',
        'enabled' => 'Maintenance mode enabled.',
        'disabled' => 'Maintenance mode disabled.',
        'active' => 'The system is under maintenance.',
        'message' => 'Maintenance message',
        'retry_after' => 'Retry after (seconds)',
    ],

    'queue' => [
        'title' => 'Queues',
        'status' => 'Queue status',
        'workers' => 'Active workers',
        'pending' => 'Pending jobs',
        'failed' => 'Failed jobs',
        'restart' => 'Restart workers',
        'restarted' => 'Workers restarted successfully.',
    ],

    'system_info' => [
        'title' => 'System information',
        'php_version' => 'PHP version',
        'laravel_version' => 'Laravel version',
        'server' => 'Server',
        'os' => 'Operating system',
        'memory_limit' => 'Memory limit',
        'max_upload' => 'Max upload size',
        'environment' => 'Environment',
        'debug_mode' => 'Debug mode',
        'timezone' => 'Timezone',
        'locale' => 'Locale',
    ],
];
