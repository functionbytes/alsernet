<?php

return [
    'module_name' => 'Job queues',
    'breadcrumb' => 'Queues',

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

    'failed_jobs' => [
        'title' => 'Failed jobs',
        'all' => 'All failed jobs',
        'total' => 'Total failed',
        'id' => 'ID',
        'queue' => 'Queue',
        'payload' => 'Payload',
        'exception' => 'Exception',
        'failed_at' => 'Failed at',
        'no_failed' => 'No failed jobs.',
        'delete' => 'Delete job',
        'delete_all' => 'Delete all',
        'delete_success' => 'Job deleted successfully.',
        'delete_all_success' => 'All failed jobs deleted.',
    ],

    'pending_jobs' => [
        'title' => 'Pending jobs',
        'all' => 'All pending jobs',
        'total' => 'Total pending',
        'queue' => 'Queue',
        'attempts' => 'Attempts',
        'reserved_at' => 'Reserved at',
        'available_at' => 'Available at',
        'created_at' => 'Created at',
        'no_pending' => 'No pending jobs.',
    ],

    'retry' => [
        'button' => 'Retry',
        'success' => 'Job queued for retry successfully.',
        'all' => 'Retry all',
        'all_success' => 'All jobs queued for retry.',
        'not_found' => 'Job not found.',
    ],

    'horizon' => [
        'title' => 'Laravel Horizon',
        'status' => 'Horizon status',
        'running' => 'Running',
        'paused' => 'Paused',
        'inactive' => 'Inactive',
        'pause' => 'Pause Horizon',
        'continue' => 'Continue Horizon',
        'workers' => 'Active workers',
        'throughput' => 'Jobs/min',
        'open_dashboard' => 'Open Horizon dashboard',
    ],

    'queues' => [
        'default' => 'Default',
        'emails' => 'Emails',
        'notifications' => 'Notifications',
        'exports' => 'Exports',
        'size' => 'Queue size',
    ],
];
