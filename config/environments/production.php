<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 애플리케이션 환경 설정
    |--------------------------------------------------------------------------
    |
    | 프로덕션 환경에 대한 설정입니다.
    |
    */
    
    // 애플리케이션 설정
    'app' => [
        'debug' => false,
        'url' => env('APP_URL', 'https://rocketsourcer.com'),
        'timezone' => 'Asia/Seoul',
        'locale' => 'ko',
        'env' => 'production',
    ],
    
    // 데이터베이스 설정
    'database' => [
        'default' => 'mysql',
        'connections' => [
            'mysql' => [
                'driver' => 'mysql',
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '3306'),
                'database' => env('DB_DATABASE', 'rocketsourcer'),
                'username' => env('DB_USERNAME', 'rocketsourcer'),
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
            'replica' => [
                'driver' => 'mysql',
                'host' => env('DB_REPLICA_HOST', '127.0.0.1'),
                'port' => env('DB_REPLICA_PORT', '3306'),
                'database' => env('DB_DATABASE', 'rocketsourcer'),
                'username' => env('DB_REPLICA_USERNAME', 'rocketsourcer_read'),
                'password' => env('DB_REPLICA_PASSWORD', ''),
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
        'prefix' => 'rocketsourcer_cache',
        'ttl' => 3600, // 기본 캐시 유효 시간 (초)
    ],
    
    // 세션 설정
    'session' => [
        'driver' => 'redis',
        'lifetime' => 120, // 세션 유효 시간 (분)
        'encrypt' => true,
        'cookie' => 'rocketsourcer_session',
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
                'level' => 'warning', // 프로덕션에서는 warning 이상만 로깅
                'days' => 30,
            ],
            'slack' => [
                'driver' => 'slack',
                'url' => env('LOG_SLACK_WEBHOOK_URL'),
                'username' => 'RocketSourcer Bot',
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
            'address' => env('MAIL_FROM_ADDRESS', 'no-reply@rocketsourcer.com'),
            'name' => env('MAIL_FROM_NAME', 'RocketSourcer'),
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
                'bucket' => env('AWS_BUCKET'),
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
            'max_attempts' => 60, // 분당 최대 요청 수
            'decay_minutes' => 1,
        ],
        'timeout' => 30, // API 요청 타임아웃 (초)
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
            'dsn' => env('SENTRY_DSN'),
        ],
        'performance' => [
            'enabled' => true,
            'slow_query_threshold' => 1000, // 느린 쿼리 임계값 (밀리초)
            'slow_request_threshold' => 5000, // 느린 요청 임계값 (밀리초)
        ],
        'health_check' => [
            'enabled' => true,
            'interval' => 60, // 헬스 체크 간격 (초)
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
        'health_check_timeout' => 300, // 헬스 체크 타임아웃 (초)
        'keep_releases' => 5, // 유지할 릴리스 수
    ],
]; 