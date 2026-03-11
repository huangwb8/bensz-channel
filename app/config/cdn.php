<?php

return [
    'mode' => env('CDN_MODE', 'origin'),

    'origin' => [
        'asset_url' => env('CDN_ASSET_URL', env('ASSET_URL')),
    ],

    'storage' => [
        'provider' => env('CDN_STORAGE_PROVIDER', 'dogecloud'),
        'access_key' => env('CDN_STORAGE_ACCESS_KEY', env('AWS_ACCESS_KEY_ID')),
        'secret_key' => env('CDN_STORAGE_SECRET_KEY', env('AWS_SECRET_ACCESS_KEY')),
        'bucket' => env('CDN_STORAGE_BUCKET', env('AWS_BUCKET')),
        'region' => env('CDN_STORAGE_REGION', env('AWS_DEFAULT_REGION', 'auto')),
        'endpoint' => env('CDN_STORAGE_ENDPOINT', env('AWS_ENDPOINT')),
        'public_url' => env('CDN_STORAGE_PUBLIC_URL', env('CDN_ASSET_URL', env('ASSET_URL'))),
        'use_path_style_endpoint' => (bool) env('CDN_STORAGE_USE_PATH_STYLE_ENDPOINT', env('AWS_USE_PATH_STYLE_ENDPOINT', false)),
    ],

    'sync' => [
        'enabled' => (bool) env('CDN_SYNC_ENABLED', false),
        'on_build' => (bool) env('CDN_SYNC_ON_BUILD', true),
        'directories' => [
            'build',
            'storage',
            'images',
            'fonts',
        ],
        'exclude_patterns' => [
            '*.map',
            '.DS_Store',
        ],
    ],

    'providers' => [
        'dogecloud' => [
            'name' => '多吉云',
            'driver' => 's3-compatible',
            'endpoint_hint' => '对象存储兼容端点',
        ],
        'qiniu' => [
            'name' => '七牛云',
            'driver' => 's3-compatible',
            'endpoint_hint' => 'Kodo S3 兼容端点',
        ],
        'upyun' => [
            'name' => '又拍云',
            'driver' => 's3-compatible',
            'endpoint_hint' => 'S3 兼容端点',
        ],
    ],
];
