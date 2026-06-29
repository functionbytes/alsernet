<?php

return [
    'module_name' => 'Cache',
    'breadcrumb' => 'Cache',

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

    'cache_settings' => [
        'title' => 'Cache settings',
        'driver' => 'Cache driver',
        'prefix' => 'Cache prefix',
        'default_ttl' => 'Default TTL (minutes)',
        'updated' => 'Cache settings updated successfully.',
    ],

    'flush_cache' => [
        'title' => 'Flush cache',
        'button' => 'Flush all cache',
        'success' => 'Cache flushed successfully.',
        'confirm' => 'Are you sure you want to flush all cache?',
        'by_tag' => 'Flush by tag',
        'tag' => 'Tag',
        'tag_flushed' => 'Cache tag ":tag" flushed successfully.',
        'by_key' => 'Flush by key',
        'key' => 'Key',
        'key_flushed' => 'Key ":key" removed from cache.',
        'key_not_found' => 'Key ":key" does not exist in cache.',
    ],

    'redis_stats' => [
        'title' => 'Redis statistics',
        'version' => 'Redis version',
        'uptime' => 'Uptime',
        'connected_clients' => 'Connected clients',
        'used_memory' => 'Used memory',
        'total_keys' => 'Total keys',
        'hits' => 'Hits',
        'misses' => 'Misses',
        'hit_rate' => 'Hit rate',
        'not_available' => 'Redis is not available.',
    ],

    'ttl' => [
        'title' => 'Time to live (TTL)',
        'seconds' => 'seconds',
        'minutes' => 'minutes',
        'hours' => 'hours',
        'days' => 'days',
        'forever' => 'Forever',
        'expired' => 'Expired',
        'expires_in' => 'Expires in :time',
    ],

    'keys' => [
        'title' => 'Cached keys',
        'key' => 'Key',
        'value' => 'Value',
        'expires' => 'Expires',
        'no_keys' => 'No keys in cache.',
    ],
];
