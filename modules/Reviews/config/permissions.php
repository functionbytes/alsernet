<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Reviews Module Permissions
    |--------------------------------------------------------------------------
    |
    | All permissions for the Reviews module, registered with Spatie Permission.
    |
    */
    'permissions' => [
        // Google Connections
        'reviews.connections.view' => 'View Google connections',
        'reviews.connections.create' => 'Create Google connections',
        'reviews.connections.edit' => 'Edit Google connections',
        'reviews.connections.delete' => 'Delete Google connections',
        'reviews.connections.revoke' => 'Revoke Google connections',

        // Google Locations
        'reviews.locations.view' => 'View Google locations',
        'reviews.locations.sync' => 'Sync Google locations',
        'reviews.locations.edit' => 'Edit location settings',

        // Reviews
        'reviews.reviews.view' => 'View reviews',
        'reviews.reviews.export' => 'Export reviews',
        'reviews.moderate' => 'Moderate reviews',

        // Replies
        'reviews.replies.view' => 'View review replies',
        'reviews.replies.create' => 'Create review replies',
        'reviews.replies.edit' => 'Edit review replies',
        'reviews.replies.delete' => 'Delete review replies',
        'reviews.replies.approve' => 'Approve review replies',
        'reviews.replies.publish' => 'Publish review replies',

        // Reply Templates
        'reviews.templates.view' => 'View reply templates',
        'reviews.templates.create' => 'Create reply templates',
        'reviews.templates.edit' => 'Edit reply templates',
        'reviews.templates.delete' => 'Delete reply templates',

        // Settings
        'reviews.settings.edit' => 'Edit review settings',
    ],
];
