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

    'better_auth' => [
        'base_url' => env('BETTER_AUTH_URL', 'http://auth:3001'),
        'internal_secret' => env('BETTER_AUTH_INTERNAL_SECRET'),
        'timeout' => (int) env('BETTER_AUTH_TIMEOUT', 5),
    ],

    'wechat' => [
        'client_id' => env('WECHAT_CLIENT_ID'),
        'client_secret' => env('WECHAT_CLIENT_SECRET'),
        'redirect' => env('WECHAT_REDIRECT_URI', rtrim((string) env('APP_URL', 'http://localhost:6542'), '/').'/auth/social/wechat/callback'),
        'timeout' => (int) env('WECHAT_TIMEOUT', 8),
    ],

    'qq' => [
        'client_id' => env('QQ_CLIENT_ID'),
        'client_secret' => env('QQ_CLIENT_SECRET'),
        'redirect' => env('QQ_REDIRECT_URI', rtrim((string) env('APP_URL', 'http://localhost:6542'), '/').'/auth/social/qq/callback'),
        'timeout' => (int) env('QQ_TIMEOUT', 8),
    ],

];
