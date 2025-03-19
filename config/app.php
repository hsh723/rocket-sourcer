<?php

return [
    'name' => env('APP_NAME', 'RocketSourcer'),
    'env' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => 'Asia/Seoul',
    'locale' => 'ko',
    
    'providers' => [
        RocketSourcer\Core\Database\DatabaseServiceProvider::class,
        RocketSourcer\Core\Routing\RouterServiceProvider::class,
        RocketSourcer\Core\Logger\LoggerServiceProvider::class,
        
        /*
         * Package Service Providers...
         */

        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        // App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
        App\Providers\DatabaseServiceProvider::class,
    ],
    
    'middleware' => [
        'global' => [
            RocketSourcer\Core\Middleware\CorsMiddleware::class,
            RocketSourcer\Core\Middleware\JsonMiddleware::class,
        ],
        'api' => [
            RocketSourcer\Core\Middleware\AuthMiddleware::class,
        ],
    ],
    
    'logging' => [
        'default' => env('LOG_CHANNEL', 'stack'),
        'channels' => [
            'stack' => [
                'driver' => 'stack',
                'channels' => ['daily'],
            ],
            'daily' => [
                'driver' => 'daily',
                'path' => storage_path('logs/app.log'),
                'level' => env('LOG_LEVEL', 'debug'),
                'days' => 14,
            ],
        ],
    ],
    
    'cors' => [
        'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', '*')),
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
        'exposed_headers' => [],
        'max_age' => 0,
        'supports_credentials' => false,
    ],
    
    'api' => [
        'coupang' => [
            'access_key' => $_ENV['COUPANG_ACCESS_KEY'] ?? '',
            'secret_key' => $_ENV['COUPANG_SECRET_KEY'] ?? '',
            'vendor_id' => $_ENV['COUPANG_VENDOR_ID'] ?? '',
        ],
    ],
    
    'cache' => [
        'driver' => 'file',
        'path' => __DIR__ . '/../storage/cache',
    ],
]; 