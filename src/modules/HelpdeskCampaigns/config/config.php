<?php

return [
    'name' => 'HelpdeskCampaigns',

    /*
     * Number of days to retain raw impression rows before cleanup.
     * Aggregated counters on campaigns are preserved after deletion.
     */
    'impressions_retention_days' => 180,

    /*
     * Outgoing webhooks to notify on campaign events.
     * Each entry: [ 'url' => 'https://...', 'secret' => '...', 'events' => ['published', 'ended'] ]
     * Leave empty to disable.
     */
    'webhooks' => [],
];
