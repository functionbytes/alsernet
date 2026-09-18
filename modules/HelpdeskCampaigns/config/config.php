<?php

return [
    'name' => 'HelpdeskCampaigns',

    /*
     * Number of days to retain raw impression rows before cleanup.
     * Aggregated counters on campaigns are preserved after deletion.
     */
    'impressions_retention_days' => 180,

    /*
     * Cache store used by FrequencyCapService for hot-path frequency-cap data.
     * null = the application's default cache store. Set to 'redis' in
     * deployments where a dedicated Redis store is configured.
     */
    'cache_store' => env('HELPDESKCAMPAIGNS_CACHE_STORE'),

    /*
     * Outgoing webhooks to notify on campaign events.
     * Each entry: [ 'url' => 'https://...', 'secret' => '...', 'events' => ['published', 'ended'] ]
     * Leave empty to disable.
     */
    'webhooks' => [],
];
