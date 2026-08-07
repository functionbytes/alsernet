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
        'delivery_rate' => 'Delivery rate',
        'delivery_rate_hint' => 'Delivered over total',
    ],

    'trend' => [
        'title' => 'Activity over the last 14 days',
        'hint' => 'Emails recorded per day and status',
        'sent' => 'Sent',
        'failed' => 'Failed',
        'queued' => 'Queued',
    ],

    'stale' => [
        'warning' => ':count email(s) have been queued for more than :hours h without confirmation.',
        'view' => 'View queued',
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
        'has_attachments' => 'With attachments',
    ],

    'actions' => [
        'view' => 'View content',
        'resend' => 'Resend',
        'delete' => 'Delete',
        'export' => 'Export CSV',
        'bulk_delete' => 'Delete selected',
        'back_to_list' => 'Back to list',
        'print' => 'Print',
        'bulk_resend' => 'Resend selected',
        'download' => 'Download',
        'copy_id' => 'Copy Message-ID',
        'resend_to' => 'Resend to another address',
        'purge' => 'Purge content',
    ],

    'purge' => [
        'confirm_title' => 'Purge email content',
        'confirm' => 'The email body (HTML/text) will be permanently removed. Metadata (subject, recipients, status) is kept. Continue?',
        'done' => 'Email content purged.',
    ],

    'pagination' => [
        'showing' => 'Showing :first–:last of :total entries',
    ],

    'bulk' => [
        'label' => 'selected',
        'confirm' => 'Delete :count entries? This action cannot be undone.',
        'none_selected' => 'Select at least one entry.',
        'resend_confirm' => 'Resend :count emails to their original recipients?',
        'resend_title' => 'Confirm bulk resend',
    ],

    'copy' => [
        'message_id_copied' => 'Message-ID copied to clipboard.',
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
        'bounced' => 'Bounced',
        'complained' => 'Marked as spam',
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
            'bcc' => 'BCC',
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
        'related_entity' => 'Related entity',
        'related_entity_hint' => 'Record linked to this email',
        'related_emails' => 'Related emails',
        'related_emails_hint' => 'Same recipient or entity',
        'no_related' => 'No related emails.',
        'purged_note' => 'This email\'s content was purged; only metadata is kept.',
    ],

    'resend' => [
        'success' => 'Email resent successfully.',
        'queued' => 'Resend queued. The email will be sent in the background.',
        'queued_to' => 'Resend to :email queued.',
        'bulk_queued' => ':count emails queued for resend.',
        'failed' => 'Could not resend the email: :error',
        'no_recipients' => 'This entry has no recipients.',
        'blocked' => 'Cannot resend: the stored content of this email is redacted or truncated.',
        'bulk_skipped' => ':count skipped (no recipients or redacted/truncated content).',
        'confirm_title' => 'Confirm resend',
        'confirm' => 'Resend this email to its original recipients?',
        'to_title' => 'Resend to another address',
        'to_label' => 'Alternative email address',
        'to_placeholder' => 'name@example.com',
        'to_hint' => 'The email will be sent only to this address (no CC).',
        'to_send' => 'Send',
    ],

    'settings' => [
        'title' => 'Email log — Settings',
        'saved' => 'Email log settings updated.',
    ],

    'csv' => [
        'uid' => 'UID',
        'date' => 'Date',
        'sent_at' => 'Sent',
        'status' => 'Status',
        'subject' => 'Subject',
        'from' => 'From',
        'to' => 'To',
        'cc' => 'CC',
        'module' => 'Module',
        'entity' => 'Entity',
        'mailable' => 'Mailable',
        'error' => 'Error',
    ],
];
