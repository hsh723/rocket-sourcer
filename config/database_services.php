<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 데이터베이스 쿼리 로깅 설정
    |--------------------------------------------------------------------------
    |
    | 데이터베이스 쿼리 로깅에 관한 설정입니다.
    | enabled: 쿼리 로깅 활성화 여부
    | threshold: 로깅할 쿼리의 최소 실행 시간 (밀리초)
    | channel: 로그 채널
    |
    */
    'query_logging' => [
        'enabled' => env('DB_QUERY_LOGGING', false),
        'threshold' => env('DB_QUERY_LOGGING_THRESHOLD', 100), // 100ms 이상 걸리는 쿼리만 로깅
        'channel' => env('DB_QUERY_LOGGING_CHANNEL', 'daily'),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | 데이터베이스 모니터링 설정
    |--------------------------------------------------------------------------
    |
    | 데이터베이스 모니터링에 관한 설정입니다.
    | enabled: 모니터링 활성화 여부
    | query_logging: 쿼리 로깅 활성화 여부
    | slow_query_threshold: 느린 쿼리 임계값 (초)
    | check_interval: 모니터링 확인 간격 (초)
    |
    */
    'monitoring' => [
        'enabled' => env('DB_MONITORING', false),
        'query_logging' => env('DB_MONITORING_QUERY_LOGGING', false),
        'slow_query_threshold' => env('DB_SLOW_QUERY_THRESHOLD', 1.0), // 1초 이상 걸리는 쿼리는 느린 쿼리로 간주
        'check_interval' => env('DB_MONITORING_CHECK_INTERVAL', 300), // 5분마다 확인
    ],
    
    /*
    |--------------------------------------------------------------------------
    | 데이터베이스 연결 풀 설정
    |--------------------------------------------------------------------------
    |
    | 데이터베이스 연결 풀에 관한 설정입니다.
    | enabled: 연결 풀 활성화 여부
    | min_connections: 최소 연결 수
    | max_connections: 최대 연결 수
    | idle_timeout: 유휴 연결 타임아웃 (초)
    | connection_lifetime: 연결 수명 (초)
    |
    */
    'connection_pool' => [
        'enabled' => env('DB_CONNECTION_POOL', false),
        'min_connections' => env('DB_MIN_CONNECTIONS', 5),
        'max_connections' => env('DB_MAX_CONNECTIONS', 20),
        'idle_timeout' => env('DB_IDLE_TIMEOUT', 600), // 10분
        'connection_lifetime' => env('DB_CONNECTION_LIFETIME', 3600), // 1시간
    ],
    
    /*
    |--------------------------------------------------------------------------
    | 데이터베이스 트랜잭션 설정
    |--------------------------------------------------------------------------
    |
    | 데이터베이스 트랜잭션에 관한 설정입니다.
    | timeout: 트랜잭션 타임아웃 (초)
    | logging: 트랜잭션 로깅 활성화 여부
    | events: 트랜잭션 이벤트 활성화 여부
    |
    */
    'transactions' => [
        'timeout' => env('DB_TRANSACTION_TIMEOUT', 30), // 30초
        'logging' => env('DB_TRANSACTION_LOGGING', false),
        'events' => [
            'enabled' => env('DB_TRANSACTION_EVENTS', false),
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | 데이터베이스 쿼리 캐시 설정
    |--------------------------------------------------------------------------
    |
    | 데이터베이스 쿼리 캐시에 관한 설정입니다.
    | enabled: 쿼리 캐시 활성화 여부
    | ttl: 기본 캐시 유효 시간 (초)
    | prefix: 캐시 키 접두사
    |
    */
    'query_cache' => [
        'enabled' => env('DB_QUERY_CACHE', false),
        'ttl' => env('DB_QUERY_CACHE_TTL', 3600), // 1시간
        'prefix' => env('DB_QUERY_CACHE_PREFIX', 'db_query_'),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | 데이터베이스 백업 설정
    |--------------------------------------------------------------------------
    |
    | 데이터베이스 백업에 관한 설정입니다.
    | path: 백업 파일 저장 경로
    | file_prefix: 백업 파일 접두사
    | max_files: 최대 백업 파일 수
    | schedules: 백업 스케줄 설정
    |
    */
    'backup' => [
        'path' => env('DB_BACKUP_PATH', storage_path('backups/database')),
        'file_prefix' => env('DB_BACKUP_FILE_PREFIX', 'backup_'),
        'max_files' => env('DB_BACKUP_MAX_FILES', 10),
        'schedules' => [
            'daily' => [
                'enabled' => env('DB_BACKUP_DAILY', true),
                'time' => env('DB_BACKUP_DAILY_TIME', '01:00'),
            ],
            'weekly' => [
                'enabled' => env('DB_BACKUP_WEEKLY', true),
                'day' => env('DB_BACKUP_WEEKLY_DAY', 0), // 0: 일요일
                'time' => env('DB_BACKUP_WEEKLY_TIME', '02:00'),
            ],
            'monthly' => [
                'enabled' => env('DB_BACKUP_MONTHLY', true),
                'day' => env('DB_BACKUP_MONTHLY_DAY', 1), // 1일
                'time' => env('DB_BACKUP_MONTHLY_TIME', '03:00'),
            ],
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | 데이터베이스 복제 설정
    |--------------------------------------------------------------------------
    |
    | 데이터베이스 복제에 관한 설정입니다.
    | enabled: 복제 활성화 여부
    | master: 마스터 연결 이름
    | slaves: 슬레이브 연결 이름 목록
    | check_interval: 복제 상태 확인 간격 (초)
    |
    */
    'replication' => [
        'enabled' => env('DB_REPLICATION', false),
        'master' => env('DB_REPLICATION_MASTER', 'mysql'),
        'slaves' => explode(',', env('DB_REPLICATION_SLAVES', '')),
        'check_interval' => env('DB_REPLICATION_CHECK_INTERVAL', 300), // 5분마다 확인
    ],
    
    /*
    |--------------------------------------------------------------------------
    | 데이터베이스 인덱스 설정
    |--------------------------------------------------------------------------
    |
    | 데이터베이스 인덱스에 관한 설정입니다.
    | auto_suggest: 인덱스 자동 제안 활성화 여부
    | analyze_interval: 인덱스 분석 간격 (초)
    |
    */
    'indexes' => [
        'auto_suggest' => env('DB_INDEX_AUTO_SUGGEST', false),
        'analyze_interval' => env('DB_INDEX_ANALYZE_INTERVAL', 86400), // 1일
    ],
]; 