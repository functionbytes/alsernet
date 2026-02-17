<?php

return [
    'name' => 'Analytics',
    'description' => 'Google Analytics integration for tracking and reporting',

    'settings' => [
        'title' => 'Analytics Settings',
        'description' => 'Configure Google Analytics integration',
        'google_analytics' => 'Google Analytics',
        'property_id' => 'Property ID',
        'property_id_placeholder' => 'Enter GA4 Property ID (9 digits)',
        'credentials' => 'Service Account Credentials',
        'credentials_placeholder' => 'Paste JSON credentials here',
        'upload_json' => 'Upload JSON File',
        'enable_dashboard_widgets' => 'Enable Dashboard Widgets',
        'cache_lifetime' => 'Cache Lifetime (minutes)',
        'validate_credentials' => 'Validate Credentials',
        'test_connection' => 'Test Connection',
        'save_settings' => 'Save Settings',
    ],

    'dashboard' => [
        'title' => 'Analytics Dashboard',
        'general' => 'General Statistics',
        'top_pages' => 'Top Pages',
        'top_browsers' => 'Top Browsers',
        'top_referrers' => 'Top Referrers',
        'period' => 'Period',
        'sessions' => 'Sessions',
        'users' => 'Users',
        'pageviews' => 'Pageviews',
        'bounce_rate' => 'Bounce Rate',
        'visitors' => 'Visitors',
        'daily_visitors' => 'Daily Visitors',
    ],

    'widgets' => [
        'general' => 'Analytics Overview',
        'pages' => 'Most Visited Pages',
        'browsers' => 'Browser Distribution',
        'referrers' => 'Traffic Sources',
    ],

    'periods' => [
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'last_7_days' => 'Last 7 Days',
        'last_30_days' => 'Last 30 Days',
        'this_month' => 'This Month',
        'last_month' => 'Last Month',
        'custom' => 'Custom Range',
    ],

    'metrics' => [
        'sessions' => 'Sessions',
        'totalUsers' => 'Total Users',
        'screenPageViews' => 'Page Views',
        'bounceRate' => 'Bounce Rate',
        'averageSessionDuration' => 'Avg. Session Duration',
        'newUsers' => 'New Users',
        'conversions' => 'Conversions',
        'eventCount' => 'Events',
        'engagementRate' => 'Engagement Rate',
    ],

    'dimensions' => [
        'date' => 'Date',
        'pagePath' => 'Page Path',
        'pageTitle' => 'Page Title',
        'country' => 'Country',
        'city' => 'City',
        'browser' => 'Browser',
        'deviceCategory' => 'Device Category',
        'operatingSystem' => 'Operating System',
        'source' => 'Source',
        'medium' => 'Medium',
        'campaign' => 'Campaign',
        'landingPage' => 'Landing Page',
        'exitPage' => 'Exit Page',
    ],

    'errors' => [
        'invalid_credentials' => 'Invalid Google Analytics credentials. Please check your service account JSON.',
        'invalid_property_id' => 'Invalid Property ID. Must be a 9-digit number.',
        'connection_failed' => 'Failed to connect to Google Analytics API.',
        'no_data' => 'No analytics data available for the selected period.',
        'not_configured' => 'Google Analytics is not configured. Please configure it in settings.',
    ],

    'success' => [
        'credentials_validated' => 'Credentials validated successfully.',
        'connection_successful' => 'Connection to Google Analytics successful.',
        'settings_saved' => 'Analytics settings saved successfully.',
    ],

    'permissions' => [
        'manage' => 'Manage Analytics',
        'view_dashboard' => 'View Analytics Dashboard',
        'view_general' => 'View General Analytics',
        'view_pages' => 'View Page Analytics',
        'view_browsers' => 'View Browser Analytics',
        'view_referrers' => 'View Referrer Analytics',
        'settings' => 'Manage Analytics Settings',
    ],
];
