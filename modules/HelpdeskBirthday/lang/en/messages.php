<?php

return [
    'module_name' => 'Birthdays',
    'campaigns_title' => 'Birthday campaigns',
    'settings_title' => 'Birthday settings',

    'default_customer_name' => 'customer',
    'mail_fallback_subject' => 'Happy birthday!',

    'campaign_prepared' => 'Campaign prepared.',
    'campaign_paused' => 'Campaign paused. No further emails will go out until you resume it.',
    'campaign_resumed' => 'Campaign resumed.',
    'campaign_cancelled' => 'Campaign cancelled. Pending emails were discarded.',
    'transition_not_allowed' => 'That action is not possible in the campaign current state.',
    'settings_saved' => 'Settings saved.',
    'coupon_missing' => 'No coupon code is configured.',
    'recipient_unsubscribed' => ':email was unsubscribed from birthday emails.',
    'recipient_requeued' => 'The email is back in the queue.',
    'retry_not_allowed' => 'Only failed emails can be retried.',
    'test_send_ok' => 'Test sent to :emails.',
    'test_send_failed' => 'The test could not be sent. :errors',
    'test_send_too_many' => 'At most 5 addresses per test.',
    'failed_requeued' => ':count failed emails are back in the queue.',
    'no_failed_to_retry' => 'There are no failed emails to retry.',
    'coupon_not_in_campaign' => 'That coupon does not belong to this campaign.',
    'bonos_retried' => 'Gestión issued :generated coupons. Still without one: :failed.',
    'no_bonos_to_retry' => 'No recipients are waiting for a coupon.',

    'status' => [
        'draft' => 'Draft',
        'scheduled' => 'Scheduled',
        'sending' => 'Sending',
        'paused' => 'Paused',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'cancelled' => 'Cancelled',
    ],

    'recipient_status' => [
        'pending' => 'Pending',
        'sending' => 'Queued',
        'sent' => 'Sent',
        'failed' => 'Failed',
        'skipped' => 'Skipped',
    ],

    'skip_reason' => [
        'suppressed' => 'Suppressed',
        'invalid_email' => 'Invalid email',
        'duplicate' => 'Duplicate',
        'cancelled' => 'Campaign cancelled',
        'no_coupon' => 'No coupon issued',
        'expired' => 'Birthday already passed',
    ],

    'source' => [
        'manual' => 'Set manually',
        'erp' => 'Validated with ERP',
    ],
];
