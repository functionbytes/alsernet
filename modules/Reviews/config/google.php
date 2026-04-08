<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Google OAuth Client Credentials
    |--------------------------------------------------------------------------
    |
    | Credentials from Google Cloud Console for OAuth 2.0 authentication
    | with Google Business Profile API.
    |
    */
    'maps_api_key' => env('GOOGLE_MAPS_API_KEY', null),

    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect_uri' => env('GOOGLE_REDIRECT_URI', env('APP_URL', 'http://localhost').'/settings/reviews/oauth/callback'),

    /*
    |--------------------------------------------------------------------------
    | OAuth Scopes
    |--------------------------------------------------------------------------
    |
    | Required scopes for Google Business Profile API access.
    |
    */
    'scopes' => [
        'openid',
        'email',
        'https://www.googleapis.com/auth/business.manage',
    ],

    /*
    |--------------------------------------------------------------------------
    | API Endpoints
    |--------------------------------------------------------------------------
    |
    | Google Business Profile API endpoints.
    |
    */
    'api' => [
        'account_management' => 'https://mybusinessaccountmanagement.googleapis.com/v1',
        'business_information' => 'https://mybusinessbusinessinformation.googleapis.com/v1',
        'reviews' => 'https://mybusiness.googleapis.com/v4', // Legacy v4 API
    ],
];
