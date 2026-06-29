<?php

return [
    // Page titles
    'page.title' => 'Sending Servers',
    'domain.title' => 'Sending Domains',
    'tracking.title' => 'Tracking Domains',
    'blacklist.title' => 'Blacklist',

    // Stats
    'stat.total' => 'Total',
    'stat.active' => 'Active',
    'stat.inactive' => 'Inactive',

    // Columns
    'col.name' => 'Name',
    'col.type' => 'Type',
    'col.status' => 'Status',
    'col.quota' => 'Quota',

    // Actions
    'action.test' => 'Test connection',
    'action.verify' => 'Verify',
    'action.edit' => 'Edit',
    'action.delete' => 'Delete',

    // Flash messages
    'flash.created' => 'Sending server created.',
    'flash.updated' => 'Sending server updated.',
    'flash.deleted' => 'Sending server deleted.',
    'flash.tested' => 'Connection successful.',
    'flash.verified' => 'Verified correctly.',

    // Status
    'status.active' => 'Active',
    'status.inactive' => 'Inactive',

    // Provider types
    'type.amazon-api' => 'Amazon SES (API)',
    'type.amazon-smtp' => 'Amazon SES (SMTP)',
    'type.sendgrid-api' => 'SendGrid (API)',
    'type.sendgrid-smtp' => 'SendGrid (SMTP)',
    'type.mailgun-api' => 'Mailgun (API)',
    'type.mailgun-smtp' => 'Mailgun (SMTP)',
    'type.sparkpost-api' => 'SparkPost (API)',
    'type.sparkpost-smtp' => 'SparkPost (SMTP)',
    'type.elasticemail-api' => 'ElasticEmail (API)',
    'type.elasticemail-smtp' => 'ElasticEmail (SMTP)',
    'type.brevo-api' => 'Brevo (API)',
    'type.smtp' => 'Generic SMTP',
    'type.sendmail' => 'Sendmail (local)',

    // Domain fields
    'domain.email' => 'Email',
    'domain.reason' => 'Reason',

    // Blacklist
    'blacklist.email' => 'Email',
    'blacklist.reason' => 'Reason',

    // Pagination (shared with campaign::page-templates)
    'pagination.showing' => 'Showing :from–:to of :total',
    'pagination.prev' => 'Previous',
    'pagination.next' => 'Next',
];
