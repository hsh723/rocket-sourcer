<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 배포 설정
    |--------------------------------------------------------------------------
    |
    | 이 파일은 애플리케이션의 배포 관련 설정을 정의합니다.
    | 배포 스크립트와 관련된 다양한 옵션을 구성할 수 있습니다.
    |
    */

    // 애플리케이션 이름
    'app_name' => env('APP_NAME', 'rocketsourcer'),

    // 배포 환경
    'environments' => [
        'production' => [
            'domain' => 'rocketsourcer.com',
            'server' => 'production.rocketsourcer.com',
            'user' => 'deployer',
            'path' => '/var/www/rocketsourcer',
            'backup_path' => '/var/backups/rocketsourcer',
            'health_check_url' => 'https://rocketsourcer.com/api/health',
            'slack_webhook' => env('SLACK_WEBHOOK_PRODUCTION', ''),
            'keep_releases' => 5,
            'deploy_modes' => ['standard', 'blue-green', 'canary'],
            'servers' => [
                'web' => [
                    'web1.rocketsourcer.com',
                    'web2.rocketsourcer.com',
                ],
                'db' => [
                    'db1.rocketsourcer.com',
                ],
                'cache' => [
                    'cache1.rocketsourcer.com',
                ],
            ],
            'shared_files' => [
                '.env',
            ],
            'shared_dirs' => [
                'storage/app',
                'storage/framework/cache',
                'storage/framework/sessions',
                'storage/framework/views',
                'storage/logs',
            ],
            'writable_dirs' => [
                'bootstrap/cache',
                'storage',
            ],
            'hooks' => [
                'before_deploy' => [
                    'backup_database',
                ],
                'after_deploy' => [
                    'clear_cache',
                    'restart_queue',
                    'restart_scheduler',
                ],
            ],
        ],
        'staging' => [
            'domain' => 'staging.rocketsourcer.com',
            'server' => 'staging.rocketsourcer.com',
            'user' => 'deployer',
            'path' => '/var/www/rocketsourcer-staging',
            'backup_path' => '/var/backups/rocketsourcer-staging',
            'health_check_url' => 'https://staging.rocketsourcer.com/api/health',
            'slack_webhook' => env('SLACK_WEBHOOK_STAGING', ''),
            'keep_releases' => 3,
            'deploy_modes' => ['standard'],
            'servers' => [
                'web' => [
                    'staging.rocketsourcer.com',
                ],
                'db' => [
                    'staging.rocketsourcer.com',
                ],
                'cache' => [
                    'staging.rocketsourcer.com',
                ],
            ],
            'shared_files' => [
                '.env',
            ],
            'shared_dirs' => [
                'storage/app',
                'storage/framework/cache',
                'storage/framework/sessions',
                'storage/framework/views',
                'storage/logs',
            ],
            'writable_dirs' => [
                'bootstrap/cache',
                'storage',
            ],
            'hooks' => [
                'before_deploy' => [
                    'backup_database',
                ],
                'after_deploy' => [
                    'clear_cache',
                    'restart_queue',
                    'restart_scheduler',
                ],
            ],
        ],
    ],

    // 배포 유형
    'deploy_types' => [
        'full' => [
            'description' => '전체 배포 (코드 + 데이터베이스 마이그레이션)',
            'tasks' => [
                'checkout_code',
                'install_dependencies',
                'setup_shared_dirs',
                'configure_application',
                'run_migrations',
                'update_symlinks',
                'restart_webserver',
                'clear_cache',
                'restart_queue',
                'restart_scheduler',
                'cleanup_old_releases',
            ],
        ],
        'code-only' => [
            'description' => '코드 전용 배포 (데이터베이스 마이그레이션 없음)',
            'tasks' => [
                'checkout_code',
                'install_dependencies',
                'setup_shared_dirs',
                'configure_application',
                'update_symlinks',
                'restart_webserver',
                'clear_cache',
                'restart_queue',
                'restart_scheduler',
                'cleanup_old_releases',
            ],
        ],
    ],

    // 배포 모드
    'deploy_modes' => [
        'standard' => [
            'description' => '표준 배포 (다운타임 발생 가능)',
            'tasks' => [
                'update_symlinks',
                'restart_webserver',
                'check_health',
            ],
        ],
        'blue-green' => [
            'description' => '블루-그린 배포 (제로 다운타임)',
            'tasks' => [
                'prepare_inactive_env',
                'check_inactive_health',
                'switch_traffic',
                'check_health',
            ],
        ],
        'canary' => [
            'description' => '카나리 배포 (점진적 트래픽 전환)',
            'tasks' => [
                'prepare_canary_env',
                'check_canary_health',
                'shift_traffic_10_percent',
                'monitor_canary',
                'shift_traffic_50_percent',
                'monitor_canary',
                'shift_traffic_100_percent',
                'check_health',
            ],
        ],
    ],

    // 헬스 체크 설정
    'health_check' => [
        'timeout' => 300,
        'interval' => 5,
        'endpoints' => [
            '/api/health',
            '/api/health/db',
            '/api/health/cache',
            '/api/health/queue',
        ],
    ],

    // 알림 설정
    'notifications' => [
        'slack' => [
            'enabled' => env('DEPLOY_SLACK_NOTIFICATIONS', true),
            'emoji' => [
                'success' => ':rocket:',
                'failure' => ':boom:',
                'warning' => ':warning:',
            ],
        ],
        'email' => [
            'enabled' => env('DEPLOY_EMAIL_NOTIFICATIONS', false),
            'recipients' => [
                'admin@rocketsourcer.com',
                'devops@rocketsourcer.com',
            ],
        ],
    ],

    // 백업 설정
    'backup' => [
        'enabled' => true,
        'database' => [
            'enabled' => true,
            'keep_daily' => 7,
            'keep_weekly' => 4,
            'keep_monthly' => 6,
        ],
        'files' => [
            'enabled' => true,
            'keep_daily' => 3,
            'keep_weekly' => 2,
            'keep_monthly' => 3,
            'include' => [
                'storage/app/public',
                'storage/app/uploads',
            ],
            'exclude' => [
                'storage/app/temp',
                'storage/logs',
            ],
        ],
    ],

    // 롤백 설정
    'rollback' => [
        'auto_rollback_on_failure' => true,
        'health_check_after_rollback' => true,
        'notify_on_rollback' => true,
    ],

    // 보안 설정
    'security' => [
        'allowed_ips' => [
            '127.0.0.1',
            // 회사 IP 주소 추가
        ],
        'require_approval' => [
            'production' => true,
            'staging' => false,
        ],
        'two_factor_auth' => [
            'production' => true,
            'staging' => false,
        ],
    ],

    // 성능 모니터링 설정
    'monitoring' => [
        'enabled' => true,
        'services' => [
            'new_relic' => [
                'enabled' => env('NEW_RELIC_ENABLED', false),
                'app_id' => env('NEW_RELIC_APP_ID', ''),
                'api_key' => env('NEW_RELIC_API_KEY', ''),
                'notify_on_deploy' => true,
            ],
            'datadog' => [
                'enabled' => env('DATADOG_ENABLED', false),
                'api_key' => env('DATADOG_API_KEY', ''),
                'app_key' => env('DATADOG_APP_KEY', ''),
                'notify_on_deploy' => true,
            ],
        ],
        'metrics' => [
            'response_time',
            'error_rate',
            'cpu_usage',
            'memory_usage',
            'database_queries',
        ],
        'thresholds' => [
            'response_time' => 500, // ms
            'error_rate' => 1.0, // %
            'cpu_usage' => 80.0, // %
            'memory_usage' => 80.0, // %
        ],
    ],
]; 