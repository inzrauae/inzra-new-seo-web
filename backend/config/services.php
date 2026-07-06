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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'mode' => env('PAYPAL_MODE', 'live'),
        'notification_email' => env('ORDER_NOTIFICATION_EMAIL', 'paypal@inzra.com'),
        'admin_key' => env('ORDER_ADMIN_KEY'),
        'frontend_origins' => env(
            'ORDER_FRONTEND_ORIGINS',
            'https://inzra.com,https://www.inzra.com,http://127.0.0.1:5500,http://localhost:5500,http://127.0.0.1:8000,http://localhost:8000'
        ),
        'catalog_path' => env(
            'STORE_CATALOG_PATH',
            realpath(base_path('../products-data.json')) ?: base_path('../products-data.json')
        ),
    ],

];
