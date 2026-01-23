<?php

return [
    /*
     * If set to false, no activities will be recorded.
     */
    'enabled' => env('ACTIVITY_LOG_ENABLED', true),

    /*
     * The eloquent model that will be used to log activity.
     * Must be the fully qualified class name of the model.
     */
    'activity_model' => \Spatie\Activitylog\Models\Activity::class,

    /*
     * The name of the table that will be created by the migration and
     * used by the Activity model reported above.
     */
    'table_name' => 'activity_log',

    /*
     * The name of the table that will be created by the migration
     * and used by the Activity model to log changes.
     */
    'properties_table_name' => 'activity_log_properties',

    'database_connection' => null,

    /*
     * When the start method is called, the subject being audited is remember in the loggable trait.
     * Here you can specify how long the subject should be remembered.
     * Specify in seconds or a valid strtotime string.
     */
    'subject_returns_soft_deleted_models' => false,

    /*
     * The number of minutes logged activity records are kept.
     */
    'log_options_retention_period_in_days' => 365,

    'default_log_name' => 'default',

    'default_auth_driver' => null,

    'default_auth_model' => \App\Models\User::class,

    'methods' => [
        'retrieve',
        'store',
        'update',
        'destroy',
    ],
];
