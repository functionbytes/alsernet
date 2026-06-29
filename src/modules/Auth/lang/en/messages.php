<?php

return [
    'module_name' => 'Authentication',
    'breadcrumb' => 'Authentication',

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

    'login' => [
        'title' => 'Sign in',
        'email' => 'Email address',
        'password' => 'Password',
        'remember_me' => 'Remember me',
        'submit' => 'Sign in',
        'failed' => 'These credentials do not match our records.',
        'throttle' => 'Too many login attempts. Please wait :seconds seconds.',
    ],

    'logout' => [
        'title' => 'Sign out',
        'success' => 'You have been signed out successfully.',
    ],

    'password' => [
        'reset' => 'Reset password',
        'request' => 'Request reset',
        'email_sent' => 'A password reset link has been sent to your email.',
        'reset_success' => 'Password reset successfully.',
        'confirm' => 'Confirm password',
        'update' => 'Update password',
        'current' => 'Current password',
        'new' => 'New password',
        'confirm_new' => 'Confirm new password',
        'incorrect' => 'The current password is incorrect.',
        'changed' => 'Password updated successfully.',
    ],

    'two_factor' => [
        'title' => 'Two-factor verification',
        'code' => 'Verification code',
        'submit' => 'Verify',
        'recovery' => 'Use recovery code',
        'enabled' => 'Two-factor authentication enabled.',
        'disabled' => 'Two-factor authentication disabled.',
        'invalid_code' => 'The code entered is not valid.',
    ],

    'magic_link' => [
        'title' => 'Magic link',
        'send' => 'Send magic link',
        'sent' => 'Magic link sent to your email.',
        'expired' => 'The link has expired. Please request a new one.',
        'used' => 'This link has already been used.',
    ],

    'sessions' => [
        'title' => 'Active sessions',
        'current' => 'Current session',
        'revoke' => 'Revoke session',
        'revoke_all' => 'Revoke all sessions',
        'revoked' => 'Session revoked successfully.',
        'last_active' => 'Last active',
    ],

    'devices' => [
        'title' => 'Devices',
        'this_device' => 'This device',
        'unknown' => 'Unknown device',
    ],

    'lockscreen' => [
        'title' => 'Screen locked',
        'message' => 'Your session is locked. Enter your password to continue.',
        'unlock' => 'Unlock',
    ],
];
