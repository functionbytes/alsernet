<?php

return [
    'module_name' => 'Database',
    'breadcrumb' => 'Database',

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

    'cleanup' => [
        'title' => 'Database cleanup',
        'button' => 'Run cleanup',
        'success' => 'Cleanup executed successfully.',
        'tables_cleaned' => ':count table(s) cleaned.',
        'confirm' => 'Are you sure? This action will permanently delete data.',
        'older_than' => 'Records older than',
        'days' => 'days',
    ],

    'truncate' => [
        'title' => 'Truncate tables',
        'button' => 'Truncate table',
        'success' => 'Table truncated successfully.',
        'confirm' => 'Are you sure you want to truncate the table ":table"?',
        'select_table' => 'Select a table',
        'warning' => 'This action will remove all records from the table.',
    ],

    'settings' => [
        'title' => 'Database settings',
        'connection' => 'Connection',
        'host' => 'Host',
        'port' => 'Port',
        'database' => 'Database name',
        'username' => 'Username',
        'password' => 'Password',
        'charset' => 'Charset',
        'collation' => 'Collation',
        'updated' => 'Settings updated successfully.',
        'test_connection' => 'Test connection',
        'connection_ok' => 'Connection successful.',
        'connection_error' => 'Connection error: :message',
    ],

    'backup' => [
        'title' => 'Database backup',
        'create' => 'Create backup',
        'created' => 'Backup created successfully.',
        'download' => 'Download backup',
        'delete' => 'Delete backup',
        'deleted' => 'Backup deleted successfully.',
        'restore' => 'Restore',
        'restored' => 'Database restored successfully.',
        'no_backups' => 'No backups available.',
        'size' => 'Size',
        'created_at' => 'Created at',
    ],

    'stats' => [
        'title' => 'Statistics',
        'total_tables' => 'Total tables',
        'total_size' => 'Total size',
        'total_records' => 'Total records',
        'table' => 'Table',
        'rows' => 'Rows',
        'size' => 'Size',
    ],
];
