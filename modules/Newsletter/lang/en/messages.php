<?php

return [
    'module_name' => 'Newsletter',
    'breadcrumb' => 'Newsletter',

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

    'subscribers' => [
        'title' => 'Subscribers',
        'all' => 'All subscribers',
        'active' => 'Active subscribers',
        'total' => 'Total subscribers',
        'new_this_month' => 'New this month',
        'export' => 'Export subscribers',
        'import' => 'Import subscribers',
        'email' => 'Email address',
        'name' => 'Name',
        'no_subscribers' => 'No subscribers registered.',
    ],

    'subscribed' => [
        'success' => 'You have subscribed successfully!',
        'already' => 'This email is already subscribed.',
        'confirm' => 'Please confirm your subscription via email.',
        'confirmed' => 'Subscription confirmed successfully.',
    ],

    'unsubscribed' => [
        'button' => 'Unsubscribe',
        'success' => 'You have unsubscribed successfully.',
        'already' => 'This email is not subscribed.',
        'link' => 'Unsubscribe',
    ],

    'popup' => [
        'title' => 'Subscribe to our newsletter',
        'description' => 'Get the latest news directly to your inbox.',
        'placeholder' => 'Your email address',
        'submit' => 'Subscribe',
        'dismiss' => 'No thanks',
        'enabled' => 'Popup enabled.',
        'disabled' => 'Popup disabled.',
    ],

    'mailjet' => [
        'title' => 'Mailjet configuration',
        'api_key' => 'API Key',
        'api_secret' => 'API Secret',
        'list_id' => 'List ID',
        'connection_ok' => 'Mailjet connection verified successfully.',
        'connection_error' => 'Could not connect to Mailjet. Please check your credentials.',
        'sync' => 'Sync with Mailjet',
        'synced' => 'Subscribers synced successfully.',
    ],

    'campaigns' => [
        'title' => 'Campaigns',
        'create' => 'New campaign',
        'send' => 'Send campaign',
        'sent' => 'Campaign sent successfully.',
        'draft' => 'Draft',
        'scheduled' => 'Scheduled',
        'sent_status' => 'Sent',
    ],
];
