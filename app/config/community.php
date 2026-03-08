<?php

return [
    'site' => [
        'name' => env('SITE_NAME', 'Bensz Channel'),
        'tagline' => env('SITE_TAGLINE', '像 QQ 频道一样清晰、快速、可扩展的 Web 社区'),
    ],

    'auth' => [
        'driver' => env('AUTH_DRIVER', 'better_auth'),
        'otp_ttl_minutes' => (int) env('AUTH_OTP_TTL', 10),
        'otp_length' => (int) env('AUTH_OTP_LENGTH', 6),
        'qr_ttl_minutes' => (int) env('AUTH_QR_TTL', 10),
        'preview_codes' => filter_var(env('AUTH_PREVIEW_CODES', true), FILTER_VALIDATE_BOOL),
        'qr_providers' => ['wechat', 'qq'],
    ],

    'admin' => [
        'name' => env('ADMIN_NAME', '频道管理员'),
        'email' => env('ADMIN_EMAIL', 'admin@example.com'),
        'password' => env('ADMIN_PASSWORD', 'admin123456'),
    ],

    'static' => [
        'enabled' => filter_var(env('STATIC_SITE_ENABLED', true), FILTER_VALIDATE_BOOL),
        'output_dir' => env('STATIC_SITE_OUTPUT_DIR', 'static'),
    ],
];
