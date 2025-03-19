<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 쿠팡 API 기본 설정
    |--------------------------------------------------------------------------
    */
    'api' => [
        'base_url' => env('COUPANG_API_BASE_URL', 'https://api-gateway.coupang.com'),
        'version' => env('COUPANG_API_VERSION', 'v2'),
        'access_key' => env('COUPANG_ACCESS_KEY'),
        'secret_key' => env('COUPANG_SECRET_KEY'),
        'vendor_id' => env('COUPANG_VENDOR_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | API 요청 설정
    |--------------------------------------------------------------------------
    */
    'request' => [
        'timeout' => env('COUPANG_API_TIMEOUT', 30),
        'connect_timeout' => env('COUPANG_API_CONNECT_TIMEOUT', 10),
        'retry' => [
            'max_attempts' => env('COUPANG_API_RETRY_MAX', 3),
            'delay' => env('COUPANG_API_RETRY_DELAY', 1000), // milliseconds
            'multiplier' => env('COUPANG_API_RETRY_MULTIPLIER', 2),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 캐시 설정
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => env('COUPANG_CACHE_ENABLED', true),
        'ttl' => env('COUPANG_CACHE_TTL', 3600), // seconds
        'prefix' => env('COUPANG_CACHE_PREFIX', 'coupang_api:'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting 설정
    |--------------------------------------------------------------------------
    */
    'rate_limit' => [
        'enabled' => env('COUPANG_RATE_LIMIT_ENABLED', true),
        'max_requests' => env('COUPANG_RATE_LIMIT_MAX', 100),
        'window' => env('COUPANG_RATE_LIMIT_WINDOW', 60), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | 로깅 설정
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => env('COUPANG_LOGGING_ENABLED', true),
        'channel' => env('COUPANG_LOG_CHANNEL', 'coupang'),
        'level' => env('COUPANG_LOG_LEVEL', 'debug'),
    ],
]; 