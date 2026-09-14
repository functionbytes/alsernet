<?php

return [
    'module_name' => 'System health',
    'breadcrumb' => 'System health',

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

    'health_status' => [
        'title' => 'Health status',
        'overall' => 'Overall status',
        'healthy' => 'Healthy',
        'unhealthy' => 'Unhealthy',
        'warning' => 'Warning',
        'check' => 'Check now',
        'last_checked' => 'Last checked',
        'services' => 'Monitored services',
        'all_ok' => 'All services are running correctly.',
        'issues_found' => 'Issues found in :count service(s).',
    ],

    'alerts' => [
        'title' => 'Alerts',
        'active' => 'Active alerts',
        'resolved' => 'Resolved alerts',
        'no_alerts' => 'No active alerts.',
        'resolve' => 'Resolve alert',
        'resolved_success' => 'Alert resolved successfully.',
        'sent_to' => 'Alert sent to :email.',
    ],

    'thresholds' => [
        'title' => 'Alert thresholds',
        'cpu' => 'CPU threshold (%)',
        'memory' => 'Memory threshold (%)',
        'disk' => 'Disk threshold (%)',
        'response_time' => 'Max response time (ms)',
        'updated' => 'Thresholds updated successfully.',
    ],

    'history' => [
        'title' => 'History',
        'period' => 'Period',
        'uptime' => 'Uptime',
        'incidents' => 'Incidents',
        'no_history' => 'No history available.',
        'export' => 'Export history',
    ],

    'checks' => [
        'database' => 'Database',
        'cache' => 'Cache',
        'queue' => 'Job queue',
        'storage' => 'Storage',
        'mail' => 'Mail',
        'redis' => 'Redis',
    ],
];
