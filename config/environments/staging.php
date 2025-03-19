<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 애플리케이션 환경 설정
    |--------------------------------------------------------------------------
    |
    | 스테이징 환경에 대한 설정입니다.
    |
    */
    
    // 애플리케이션 설정
    'app' => [
        'debug' => env('APP_DEBUG', true), // 스테이징에서는 디버그 모드 활성화 가능
        'url' => env('APP_URL', 'https://staging.rocketsourcer.com'),
        'timezone' => 'Asia/Seoul',
        'locale' => 'ko',
        'env' => 'staging',
    ],
    
    // 데이터베이스 설정
    'database' => [
        'default' => 'mysql',
        'connections' => [
            'mysql' => [
                'driver' => 'mysql',
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '3306'),
                'database' => env('DB_DATABASE', 'rocketsourcer_staging'),
                'username' => env('DB_USERNAME', 'rocketsourcer_staging'),
                'password' => env('DB_PASSWORD', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
                'engine' => null,
                'options' => [
                    \PDO::ATTR_PERSISTENT => true,
                    \PDO::ATTR_EMULATE_PREPARES => false,
                ],
            ],
        ],
        'migrations' => 'migrations',
        'redis' => [
            'client' => 'predis',
            'default' => [
                'host' => env('REDIS_HOST', '127.0.0.1'),
                'password' => env('REDIS_PASSWORD', null),
                'port' => env('REDIS_PORT', 6379),
                'database' => 0,
            ],
            'cache' => [
                'host' => env('REDIS_HOST', '127.0.0.1'),
                'password' => env('REDIS_PASSWORD', null),
                'port' => env('REDIS_PORT', 6379),
                'database' => 1,
            ],
            'session' => [
                'host' => env('REDIS_HOST', '127.0.0.1'),
                'password' => env('REDIS_PASSWORD', null),
                'port' => env('REDIS_PORT', 6379),
                'database' => 2,
            ],
            'queue' => [
                'host' => env('REDIS_HOST', '127.0.0.1'),
                'password' => env('REDIS_PASSWORD', null),
                'port' => env('REDIS_PORT', 6379),
                'database' => 3,
            ],
        ],
    ],
    
    // 캐시 설정
    'cache' => [
        'default' => 'redis',
        'stores' => [
            'redis' => [
                'driver' => 'redis',
                'connection' => 'cache',
            ],
            'file' => [
                'driver' => 'file',
                'path' => storage_path('framework/cache/data'),
            ],
        ],
        'prefix' => 'rocketsourcer_staging_cache',
        'ttl' => 3600, // 기본 캐시 유효 시간 (초)
    ],
    
    // 세션 설정
    'session' => [
        'driver' => 'redis',
        'lifetime' => 120, // 세션 유효 시간 (분)
        'encrypt' => true,
        'cookie' => 'rocketsourcer_staging_session',
        'secure' => true, // HTTPS에서만 쿠키 전송
        'same_site' => 'lax',
    ],
    
    // 큐 설정
    'queue' => [
        'default' => 'redis',
        'connections' => [
            'redis' => [
                'driver' => 'redis',
                'connection' => 'queue',
                'queue' => 'default',
                'retry_after' => 90, // 작업 재시도 시간 (초)
                'block_for' => null,
            ],
        ],
        'failed' => [
            'driver' => 'database',
            'database' => 'mysql',
            'table' => 'failed_jobs',
        ],
    ],
    
    // 로깅 설정
    'logging' => [
        'default' => 'stack',
        'channels' => [
            'stack' => [
                'driver' => 'stack',
                'channels' => ['daily', 'slack'],
                'ignore_exceptions' => false,
            ],
            'daily' => [
                'driver' => 'daily',
                'path' => storage_path('logs/app.log'),
                'level' => 'debug', // 스테이징에서는 모든 로그 레벨 기록
                'days' => 14,
            ],
            'slack' => [
                'driver' => 'slack',
                'url' => env('LOG_SLACK_WEBHOOK_URL'),
                'username' => 'RocketSourcer Staging Bot',
                'emoji' => ':rocket:',
                'level' => 'error', // 에러 이상만 Slack으로 전송
            ],
        ],
    ],
    
    // 메일 설정
    'mail' => [
        'driver' => 'smtp',
        'host' => env('MAIL_HOST', 'smtp.mailgun.org'),
        'port' => env('MAIL_PORT', 587),
        'encryption' => env('MAIL_ENCRYPTION', 'tls'),
        'username' => env('MAIL_USERNAME'),
        'password' => env('MAIL_PASSWORD'),
        'from' => [
            'address' => env('MAIL_FROM_ADDRESS', 'no-reply@staging.rocketsourcer.com'),
            'name' => env('MAIL_FROM_NAME', 'RocketSourcer Staging'),
        ],
    ],
    
    // 파일 스토리지 설정
    'filesystems' => [
        'default' => 's3',
        'disks' => [
            'local' => [
                'driver' => 'local',
                'root' => storage_path('app'),
            ],
            's3' => [
                'driver' => 's3',
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
                'region' => env('AWS_DEFAULT_REGION', 'ap-northeast-2'),
                'bucket' => env('AWS_BUCKET_STAGING', 'rocketsourcer-staging'),
                'url' => env('AWS_URL'),
                'endpoint' => env('AWS_ENDPOINT'),
                'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            ],
        ],
    ],
    
    // API 설정
    'api' => [
        'throttle' => [
            'enabled' => true,
            'max_attempts' => 120, // 스테이징에서는 더 많은 요청 허용
            'decay_minutes' => 1,
        ],
        'timeout' => 60, // 스테이징에서는 더 긴 타임아웃 설정
    ],
    
    // 보안 설정
    'security' => [
        'headers' => [
            'x-frame-options' => 'SAMEORIGIN',
            'x-xss-protection' => '1; mode=block',
            'x-content-type-options' => 'nosniff',
            'strict-transport-security' => 'max-age=31536000; includeSubDomains',
            'content-security-policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data: https://cdn.jsdelivr.net; font-src 'self' https://cdn.jsdelivr.net; connect-src 'self';",
        ],
        'ssl' => [
            'enabled' => true,
            'hsts' => true,
        ],
    ],
    
    // 모니터링 설정
    'monitoring' => [
        'enabled' => true,
        'error_tracking' => [
            'enabled' => true,
            'service' => env('ERROR_TRACKING_SERVICE', 'sentry'),
            'dsn' => env('SENTRY_DSN_STAGING'),
        ],
        'performance' => [
            'enabled' => true,
            'slow_query_threshold' => 500, // 스테이징에서는 더 낮은 임계값 설정
            'slow_request_threshold' => 2000, // 스테이징에서는 더 낮은 임계값 설정
        ],
        'health_check' => [
            'enabled' => true,
            'interval' => 30, // 스테이징에서는 더 짧은 간격으로 체크
            'endpoints' => [
                '/api/health',
                '/api/status',
            ],
        ],
    ],
    
    // 배포 설정
    'deployment' => [
        'strategy' => 'blue_green', // blue_green, rolling, canary
        'auto_rollback' => true,
        'health_check_timeout' => 180, // 스테이징에서는 더 짧은 타임아웃 설정
        'keep_releases' => 3, // 스테이징에서는 더 적은 릴리스 유지
    ],
    
    // 테스트 설정 (스테이징 환경 전용)
    'testing' => [
        'enabled' => true,
        'data_seeding' => true, // 테스트 데이터 시딩 활성화
        'reset_on_deploy' => true, // 배포 시 데이터베이스 초기화
    ],
]; 