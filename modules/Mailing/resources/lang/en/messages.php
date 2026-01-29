<?php

return [
    // Setting Mailer Test
    'setting' => [
        'mailer' => [
            'test' => [
                'email_subject' => 'Mail Server Configuration Test',
                'title' => 'Mail Server Test',
                'message' => 'This is a test email to verify your mail server configuration is working correctly.',
                'success' => 'If you are reading this message, your email configuration is working properly.',
                'details' => 'Test Details:',
                'sent_at' => 'Sent at:',
                'mailer' => 'Mailer:',
            ],
        ],
    ],

    // Subscription Done
    'subscription_done' => [
        'email_subject' => 'Subscription Confirmed - Welcome!',
        'confirmed' => 'Confirmed',
        'title' => 'Subscription Complete!',
        'greeting' => 'Hello :name,',
        'message' => 'Thank you for your subscription! Your account has been successfully activated.',
        'details_title' => 'Subscription Details',
        'customer' => 'Customer',
        'plan' => 'Plan',
        'status' => 'Status',
        'active' => 'Active',
        'date' => 'Date',
        'next_steps' => 'You can now access all the features of your plan. Click the button below to get started.',
        'view_dashboard' => 'View Dashboard',
        'questions' => 'If you have any questions or need assistance, please don\'t hesitate to contact our support team.',
    ],

    // General
    'all_rights_reserved' => 'All rights reserved.',
];
