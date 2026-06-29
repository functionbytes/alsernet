<?php

return [
    'messages' => [
        'campaign_created' => 'Campaign created successfully.',
        'campaign_updated' => 'Campaign updated successfully.',
        'campaign_deleted' => 'Campaign deleted successfully.',
        'campaign_activated' => 'Campaign activated successfully.',
        'campaign_paused' => 'Campaign paused successfully.',
        'campaign_sent' => 'Campaign sent successfully.',
        'template_created' => 'Campaign template created successfully.',
        'template_updated' => 'Campaign template updated successfully.',
        'template_deleted' => 'Campaign template deleted successfully.',
        'impression_tracked' => 'Impression tracked.',
        'settings_saved' => 'Campaign settings saved successfully.',
    ],

    'fields' => [
        'name' => 'Name',
        'subject' => 'Subject',
        'body' => 'Content',
        'status' => 'Status',
        'trigger' => 'Trigger',
        'target' => 'Target audience',
        'sent_at' => 'Sent at',
        'scheduled_at' => 'Scheduled for',
        'open_rate' => 'Open rate',
        'click_rate' => 'Click rate',
        'impressions' => 'Impressions',
        'conversions' => 'Conversions',
    ],

    'status' => [
        'draft' => 'Draft',
        'active' => 'Active',
        'paused' => 'Paused',
        'sent' => 'Sent',
        'archived' => 'Archived',
    ],

    'trigger' => [
        'manual' => 'Manual',
        'on_new_conversation' => 'On new conversation',
        'on_ticket_created' => 'On ticket created',
        'on_ticket_closed' => 'On ticket closed',
        'scheduled' => 'Scheduled',
        'url_match' => 'On URL match',
    ],

    'actions' => [
        'create' => 'Create campaign',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'activate' => 'Activate',
        'pause' => 'Pause',
        'send' => 'Send now',
        'preview' => 'Preview',
        'duplicate' => 'Duplicate',
        'export_stats' => 'Export statistics',
    ],
];
