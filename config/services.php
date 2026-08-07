<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL') . '/auth/google/callback'),
    ],

    'meta' => [
        'pixel_id' => env('META_PIXEL_ID', '2121056755424403'),
        'access_token' => env('META_CAPI_ACCESS_TOKEN'),
        'test_event_code' => env('META_CAPI_TEST_EVENT_CODE'),
        'api_version' => env('META_CAPI_API_VERSION', 'v21.0'),
        'enabled' => filter_var(env('META_CAPI_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        // Do not share visitor data with Meta for European Region (special-category / religious content terms).
        'block_european_tracking' => filter_var(env('META_BLOCK_EUROPEAN_TRACKING', true), FILTER_VALIDATE_BOOLEAN),
        // Do not send hashed name/email (advanced matching) — reduces special-category association risk.
        'advanced_matching' => filter_var(env('META_ADVANCED_MATCHING', false), FILTER_VALIDATE_BOOLEAN),
    ],

];
