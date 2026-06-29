<?php

return [
    'title' => 'Email log',
    'subtitle' => 'Centralized record of the emails sent by the system',

    'stats' => [
        'total' => 'Total recorded',
        'total_hint' => 'All entries',
        'sent' => 'Delivered to server',
        'sent_hint' => 'Accepted by the transport',
        'failed' => 'Failed',
        'failed_hint' => 'With a sending error',
        'queued' => 'Queued',
        'queued_hint' => 'Awaiting confirmation',
        'today' => 'Today',
        'today_hint' => 'Recorded today',
    ],

    'filters' => [
        'heading' => 'Search filters',
        'description' => 'Find emails using multiple filter criteria',
        'search_placeholder' => 'Search by subject, recipient or sender...',
        'all_modules' => 'All modules',
        'all_statuses' => 'All statuses',
        'date_range' => 'Date range',
        'per_page' => 'Rows per page',
        'per_page_option' => ':n / page',
        'search' => 'Search',
        'clear' => 'Clear filters',
    ],

    'table' => [
        'subject' => 'Subject',
        'recipient' => 'Recipient',
        'module' => 'Module',
        'status' => 'Status',
        'date' => 'Date',
        'actions' => 'Actions',
        'empty' => 'No emails recorded yet',
        'select_all' => 'Select all',
    ],

    'actions' => [
        'view' => 'View content',
        'resend' => 'Resend',
        'delete' => 'Delete',
        'export' => 'Export CSV',
        'bulk_delete' => 'Delete selected',
        'back_to_list' => 'Back to list',
        'print' => 'Print',
    ],

    'pagination' => [
        'showing' => 'Showing :first–:last of :total entries',
    ],

    'bulk' => [
        'label' => 'selected',
        'confirm' => 'Delete :count entries? This action cannot be undone.',
        'none_selected' => 'Select at least one entry.',
    ],

    'confirm' => [
        'title' => 'Confirm action',
        'delete_title' => 'Confirm deletion',
        'delete_one' => 'Delete this email log entry? This action cannot be undone.',
        'accept' => 'Accept',
        'cancel' => 'Cancel',
    ],

    'deleted' => [
        'one' => 'Email log entry deleted.',
        'many' => ':count entries deleted.',
    ],

    'status' => [
        'queued' => 'Queued',
        'sent' => 'Sent',
        'failed' => 'Failed',
    ],

    'preview' => [
        'title' => 'Email preview',
        'heading' => 'Email content',
        'desktop' => 'Desktop',
        'mobile' => 'Mobile',
        'footer_note' => 'This is the exact content of the email recorded by the system.',
        'no_content' => 'The email content is not available.',
        'detail' => 'Email details',
        'detail_hint' => 'Information about the recorded email',
        'field' => [
            'subject' => 'Subject',
            'from' => 'From',
            'to' => 'To',
            'cc' => 'CC',
            'reply_to' => 'Reply-To',
            'status' => 'Status',
            'module' => 'Module',
            'entity' => 'Entity',
            'message_id' => 'Message-ID',
            'attachments' => 'Attachments',
            'sent_at' => 'Sent at',
            'created_at' => 'Recorded',
            'causer' => 'Triggered by',
            'error' => 'Error',
        ],
        'quick_actions' => 'Quick actions',
        'quick_actions_hint' => 'Available options',
    ],

    'resend' => [
        'success' => 'Email resent successfully.',
        'queued' => 'Resend queued. The email will be sent in the background.',
        'failed' => 'Could not resend the email: :error',
        'no_recipients' => 'This entry has no recipients.',
        'confirm_title' => 'Confirm resend',
        'confirm' => 'Resend this email to its original recipients?',
    ],

    'settings' => [
        'title' => 'Email log — Settings',
        'saved' => 'Email log settings updated.',
    ],
];
