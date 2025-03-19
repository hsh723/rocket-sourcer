<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Events\QueryExecuted;
use App\Services\Database\QueryOptimizer;
use App\Services\Database\DatabaseMonitor;
use App\Services\Database\ConnectionPoolManager;
use App\Services\Database\MigrationService;
use App\Services\Database\BackupService;
use App\Services\Database\BackupScheduler;
use App\Services\Database\ReplicationManager;
use App\Services\Database\IndexManager;
use App\Services\Database\QueryCache;
use App\Services\Database\TransactionManager;
use App\Services\Cache\AdvancedCacheService;

/**
 * 데이터베이스 서비스 프로바이더
 * 
 * 데이터베이스 관련 서비스를 등록하고 설정합니다.
 */
class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * 서비스 등록
     *
     * @return void
     */
    public function register()
    {
        // 쿼리 최적화 서비스 등록
        $this->app->singleton(QueryOptimizer::class, function ($app) {
            return new QueryOptimizer();
        });
        
        // 데이터베이스 모니터링 서비스 등록
        $this->app->singleton(DatabaseMonitor::class, function ($app) {
            return new DatabaseMonitor(
                $app->make(QueryOptimizer::class)
            );
        });
        
        // 연결 풀 관리자 등록
        $this->app->singleton(ConnectionPoolManager::class, function ($app) {
            return new ConnectionPoolManager();
        });
        
        // 마이그레이션 서비스 등록
        $this->app->singleton(MigrationService::class, function ($app) {
            return new MigrationService();
        });
        
        // 백업 서비스 등록
        $this->app->singleton(BackupService::class, function ($app) {
            return new BackupService();
        });
        
        // 백업 스케줄러 등록
        $this->app->singleton(BackupScheduler::class, function ($app) {
            return new BackupScheduler(
                $app->make(BackupService::class)
            );
        });
        
        // 복제 관리자 등록
        $this->app->singleton(ReplicationManager::class, function ($app) {
            return new ReplicationManager();
        });
        
        // 인덱스 관리자 등록
        $this->app->singleton(IndexManager::class, function ($app) {
            return new IndexManager(
                $app->make(QueryOptimizer::class)
            );
        });
        
        // 쿼리 캐시 등록
        $this->app->singleton(QueryCache::class, function ($app) {
            $cacheService = null;
            
            if ($app->bound(AdvancedCacheService::class)) {
                $cacheService = $app->make(AdvancedCacheService::class);
            }
            
            return new QueryCache($cacheService);
        });
        
        // 트랜잭션 관리자 등록
        $this->app->singleton(TransactionManager::class, function ($app) {
            return new TransactionManager();
        });
    }

    /**
     * 서비스 부트스트랩
     *
     * @return void
     */
    public function boot()
    {
        // 쿼리 로깅 설정
        if (config('database.query_logging.enabled', false)) {
            DB::listen(function (QueryExecuted $query) {
                $sql = $query->sql;
                $bindings = $query->bindings;
                $time = $query->time;
                
                // 쿼리 로깅 임계값 (밀리초)
                $threshold = config('database.query_logging.threshold', 0);
                
                // 임계값보다 오래 걸린 쿼리만 로깅
                if ($time >= $threshold) {
                    $connection = $query->connection->getName();
                    
                    // 바인딩 값 마스킹 (비밀번호 등)
                    $maskedBindings = $this->maskSensitiveBindings($bindings);
                    
                    Log::channel(config('database.query_logging.channel', 'daily'))->info(
                        'Query executed',
                        [
                            'sql' => $sql,
                            'bindings' => $maskedBindings,
                            'time' => $time,
                            'connection' => $connection
                        ]
                    );
                }
            });
        }
        
        // 쿼리 캐시 매크로 등록
        if (config('database.query_cache.enabled', false)) {
            $this->app->make(QueryCache::class)->registerEloquentMacros();
        }
        
        // 트랜잭션 이벤트 등록
        if (config('database.transaction_events.enabled', false)) {
            $this->app->make(TransactionManager::class)->registerTransactionEvents();
        }
        
        // 데이터베이스 모니터링 활성화
        if (config('database.monitoring.enabled', false)) {
            $monitor = $this->app->make(DatabaseMonitor::class);
            $monitor->setEnabled(true);
            
            if (config('database.monitoring.query_logging', false)) {
                $monitor->enableQueryLogging();
            }
        }
    }
    
    /**
     * 민감한 바인딩 값을 마스킹합니다.
     *
     * @param array $bindings 쿼리 바인딩
     * @return array 마스킹된 바인딩
     */
    protected function maskSensitiveBindings(array $bindings)
    {
        $sensitiveFields = [
            'password', 'password_confirmation', 'current_password',
            'secret', 'token', 'api_key', 'private_key', 'secret_key',
            'credit_card', 'card_number', 'cvv', 'ssn'
        ];
        
        $maskedBindings = [];
        
        foreach ($bindings as $key => $value) {
            // 키가 문자열인 경우 (이름 있는 바인딩)
            if (is_string($key)) {
                $isSensitive = false;
                
                foreach ($sensitiveFields as $field) {
                    if (stripos($key, $field) !== false) {
                        $isSensitive = true;
                        break;
                    }
                }
                
                $maskedBindings[$key] = $isSensitive ? '********' : $value;
            } else {
                // 키가 숫자인 경우 (위치 기반 바인딩)
                $maskedBindings[$key] = $value;
            }
        }
        
        return $maskedBindings;
    }
} 