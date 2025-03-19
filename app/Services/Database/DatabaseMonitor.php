<?php

namespace App\Services\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * 데이터베이스 모니터링 서비스
 * 
 * 데이터베이스 성능 및 상태를 모니터링하고 분석하는 서비스입니다.
 * 쿼리 성능, 테이블 크기, 인덱스 사용량 등을 추적합니다.
 */
class DatabaseMonitor
{
    /**
     * 모니터링 활성화 여부
     * 
     * @var bool
     */
    protected $enabled = true;
    
    /**
     * 쿼리 로깅 활성화 여부
     * 
     * @var bool
     */
    protected $queryLoggingEnabled = false;
    
    /**
     * 느린 쿼리 임계값 (밀리초)
     * 
     * @var int
     */
    protected $slowQueryThreshold = 100;
    
    /**
     * 모니터링 데이터 캐시 키
     * 
     * @var string
     */
    protected $cacheKey = 'db_monitor_data';
    
    /**
     * 모니터링 데이터 캐시 TTL (초)
     * 
     * @var int
     */
    protected $cacheTtl = 3600; // 1시간
    
    /**
     * 쿼리 최적화 서비스 인스턴스
     * 
     * @var QueryOptimizer|null
     */
    protected $queryOptimizer;
    
    /**
     * 생성자
     * 
     * @param QueryOptimizer|null $queryOptimizer 쿼리 최적화 서비스
     */
    public function __construct(QueryOptimizer $queryOptimizer = null)
    {
        $this->queryOptimizer = $queryOptimizer;
    }
    
    /**
     * 모니터링을 활성화합니다.
     * 
     * @param bool $enabled 활성화 여부
     * @return $this
     */
    public function setEnabled(bool $enabled)
    {
        $this->enabled = $enabled;
        return $this;
    }
    
    /**
     * 쿼리 로깅을 활성화합니다.
     * 
     * @param bool $enabled 활성화 여부
     * @return $this
     */
    public function enableQueryLogging(bool $enabled = true)
    {
        $this->queryLoggingEnabled = $enabled;
        
        if ($enabled) {
            DB::enableQueryLog();
        } else {
            DB::disableQueryLog();
        }
        
        return $this;
    }
    
    /**
     * 느린 쿼리 임계값을 설정합니다.
     * 
     * @param int $threshold 임계값 (밀리초)
     * @return $this
     */
    public function setSlowQueryThreshold(int $threshold)
    {
        $this->slowQueryThreshold = $threshold;
        return $this;
    }
    
    /**
     * 데이터베이스 상태를 모니터링합니다.
     * 
     * @return array 모니터링 결과
     */
    public function monitorStatus()
    {
        if (!$this->enabled) {
            return ['enabled' => false, 'message' => '모니터링이 비활성화되어 있습니다.'];
        }
        
        try {
            $result = [
                'timestamp' => now()->toDateTimeString(),
                'database' => config('database.default'),
                'connection_status' => $this->checkConnectionStatus(),
                'tables' => $this->getTableStats(),
                'queries' => $this->getQueryStats(),
                'slow_queries' => $this->getSlowQueries(),
                'suggestions' => $this->getSuggestions()
            ];
            
            // 모니터링 데이터 캐싱
            $this->cacheMonitoringData($result);
            
            return $result;
        } catch (\Exception $e) {
            Log::error("데이터베이스 모니터링 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return [
                'error' => $e->getMessage(),
                'timestamp' => now()->toDateTimeString()
            ];
        }
    }
    
    /**
     * 데이터베이스 연결 상태를 확인합니다.
     * 
     * @return array 연결 상태
     */
    public function checkConnectionStatus()
    {
        try {
            $startTime = microtime(true);
            DB::connection()->getPdo();
            $endTime = microtime(true);
            
            $connectionTime = round(($endTime - $startTime) * 1000, 2); // 밀리초
            
            return [
                'connected' => true,
                'connection_time_ms' => $connectionTime,
                'driver' => DB::connection()->getDriverName(),
                'database_name' => DB::connection()->getDatabaseName()
            ];
        } catch (\Exception $e) {
            Log::error("데이터베이스 연결 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return [
                'connected' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 테이블 통계를 가져옵니다.
     * 
     * @return array 테이블 통계
     */
    public function getTableStats()
    {
        try {
            $tables = [];
            $tableNames = Schema::getConnection()->getDoctrineSchemaManager()->listTableNames();
            
            foreach ($tableNames as $tableName) {
                $tableInfo = $this->getTableInfo($tableName);
                $tables[$tableName] = $tableInfo;
            }
            
            // 테이블 크기별 정렬
            uasort($tables, function ($a, $b) {
                return $b['size_bytes'] <=> $a['size_bytes'];
            });
            
            return [
                'count' => count($tables),
                'tables' => $tables
            ];
        } catch (\Exception $e) {
            Log::error("테이블 통계 조회 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return [
                'count' => 0,
                'tables' => [],
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 특정 테이블의 정보를 가져옵니다.
     * 
     * @param string $tableName 테이블 이름
     * @return array 테이블 정보
     */
    public function getTableInfo(string $tableName)
    {
        try {
            // 테이블 크기 및 행 수 조회 (MySQL 기준)
            $sizeInfo = DB::select("
                SELECT 
                    table_name AS 'table',
                    table_rows AS 'rows',
                    data_length + index_length AS 'size_bytes',
                    index_length AS 'index_size_bytes'
                FROM information_schema.TABLES
                WHERE table_schema = DATABASE()
                AND table_name = ?
            ", [$tableName]);
            
            $tableInfo = !empty($sizeInfo) ? (array) $sizeInfo[0] : [
                'table' => $tableName,
                'rows' => 0,
                'size_bytes' => 0,
                'index_size_bytes' => 0
            ];
            
            // 인덱스 정보 조회
            $indexes = DB::select("SHOW INDEX FROM {$tableName}");
            $indexInfo = [];
            
            foreach ($indexes as $index) {
                $indexName = $index->Key_name;
                
                if (!isset($indexInfo[$indexName])) {
                    $indexInfo[$indexName] = [
                        'name' => $indexName,
                        'columns' => [],
                        'unique' => $index->Non_unique == 0,
                        'type' => $index->Index_type
                    ];
                }
                
                $indexInfo[$indexName]['columns'][] = $index->Column_name;
            }
            
            // 컬럼 정보 조회
            $columns = Schema::getColumnListing($tableName);
            
            // 결과 포맷팅
            $result = [
                'name' => $tableName,
                'rows' => $tableInfo['rows'],
                'size_bytes' => $tableInfo['size_bytes'],
                'size_human' => $this->formatBytes($tableInfo['size_bytes']),
                'index_size_bytes' => $tableInfo['index_size_bytes'],
                'index_size_human' => $this->formatBytes($tableInfo['index_size_bytes']),
                'columns_count' => count($columns),
                'indexes_count' => count($indexInfo),
                'indexes' => array_values($indexInfo)
            ];
            
            return $result;
        } catch (\Exception $e) {
            Log::error("테이블 정보 조회 오류: {$e->getMessage()}", [
                'table' => $tableName,
                'exception' => $e
            ]);
            
            return [
                'name' => $tableName,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 쿼리 통계를 가져옵니다.
     * 
     * @return array 쿼리 통계
     */
    public function getQueryStats()
    {
        if (!$this->queryLoggingEnabled) {
            return [
                'logging_enabled' => false,
                'message' => '쿼리 로깅이 비활성화되어 있습니다.'
            ];
        }
        
        try {
            $queryLog = DB::getQueryLog();
            $totalQueries = count($queryLog);
            $totalTime = 0;
            $slowQueries = 0;
            
            foreach ($queryLog as $query) {
                $time = $query['time'] ?? 0;
                $totalTime += $time;
                
                if ($time >= $this->slowQueryThreshold) {
                    $slowQueries++;
                }
            }
            
            $avgTime = $totalQueries > 0 ? $totalTime / $totalQueries : 0;
            
            return [
                'logging_enabled' => true,
                'total_queries' => $totalQueries,
                'total_time_ms' => round($totalTime, 2),
                'avg_time_ms' => round($avgTime, 2),
                'slow_queries_count' => $slowQueries,
                'slow_query_threshold_ms' => $this->slowQueryThreshold
            ];
        } catch (\Exception $e) {
            Log::error("쿼리 통계 조회 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return [
                'logging_enabled' => true,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 느린 쿼리 목록을 가져옵니다.
     * 
     * @param int $limit 최대 개수
     * @return array 느린 쿼리 목록
     */
    public function getSlowQueries(int $limit = 10)
    {
        if (!$this->queryLoggingEnabled) {
            return [
                'logging_enabled' => false,
                'message' => '쿼리 로깅이 비활성화되어 있습니다.'
            ];
        }
        
        try {
            $queryLog = DB::getQueryLog();
            $slowQueries = [];
            
            foreach ($queryLog as $index => $query) {
                $time = $query['time'] ?? 0;
                
                if ($time >= $this->slowQueryThreshold) {
                    $slowQueries[] = [
                        'sql' => $query['query'],
                        'bindings' => $query['bindings'],
                        'time_ms' => round($time, 2),
                        'index' => $index
                    ];
                }
            }
            
            // 실행 시간별 정렬
            usort($slowQueries, function ($a, $b) {
                return $b['time_ms'] <=> $a['time_ms'];
            });
            
            // 최대 개수 제한
            $slowQueries = array_slice($slowQueries, 0, $limit);
            
            return [
                'logging_enabled' => true,
                'count' => count($slowQueries),
                'threshold_ms' => $this->slowQueryThreshold,
                'queries' => $slowQueries
            ];
        } catch (\Exception $e) {
            Log::error("느린 쿼리 조회 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return [
                'logging_enabled' => true,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 최적화 제안을 가져옵니다.
     * 
     * @return array 최적화 제안
     */
    public function getSuggestions()
    {
        if (!$this->queryOptimizer) {
            return [
                'message' => '쿼리 최적화 서비스가 설정되지 않았습니다.'
            ];
        }
        
        try {
            $suggestions = [];
            
            // 쿼리 분석 제안
            if ($this->queryLoggingEnabled) {
                $querySuggestions = $this->queryOptimizer->analyzeQueries();
                $suggestions['queries'] = $querySuggestions;
            }
            
            // 인덱스 제안
            $indexSuggestions = [];
            $tableNames = Schema::getConnection()->getDoctrineSchemaManager()->listTableNames();
            
            foreach ($tableNames as $tableName) {
                $tableIndexSuggestions = $this->queryOptimizer->suggestIndexes($tableName);
                
                if (!empty($tableIndexSuggestions)) {
                    $indexSuggestions[$tableName] = $tableIndexSuggestions;
                }
            }
            
            $suggestions['indexes'] = $indexSuggestions;
            
            // 테이블 최적화 제안
            $tableSuggestions = $this->getTableOptimizationSuggestions();
            $suggestions['tables'] = $tableSuggestions;
            
            return $suggestions;
        } catch (\Exception $e) {
            Log::error("최적화 제안 조회 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return [
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 테이블 최적화 제안을 가져옵니다.
     * 
     * @return array 테이블 최적화 제안
     */
    protected function getTableOptimizationSuggestions()
    {
        try {
            $suggestions = [];
            $tableStats = $this->getTableStats();
            
            foreach ($tableStats['tables'] as $tableName => $tableInfo) {
                $tableSuggestions = [];
                
                // 큰 테이블 감지 (10MB 이상)
                if ($tableInfo['size_bytes'] > 10 * 1024 * 1024) {
                    $tableSuggestions[] = [
                        'type' => 'large_table',
                        'message' => "테이블 크기가 큽니다 ({$tableInfo['size_human']}). 파티셔닝이나 샤딩을 고려하세요."
                    ];
                }
                
                // 인덱스 크기가 데이터 크기보다 큰 경우
                if ($tableInfo['index_size_bytes'] > ($tableInfo['size_bytes'] - $tableInfo['index_size_bytes'])) {
                    $tableSuggestions[] = [
                        'type' => 'large_indexes',
                        'message' => "인덱스 크기({$tableInfo['index_size_human']})가 데이터 크기보다 큽니다. 불필요한 인덱스를 제거하세요."
                    ];
                }
                
                // 인덱스가 없는 경우
                if ($tableInfo['indexes_count'] == 0 && $tableInfo['rows'] > 1000) {
                    $tableSuggestions[] = [
                        'type' => 'no_indexes',
                        'message' => "테이블에 인덱스가 없습니다. 자주 조회하는 컬럼에 인덱스를 추가하세요."
                    ];
                }
                
                if (!empty($tableSuggestions)) {
                    $suggestions[$tableName] = $tableSuggestions;
                }
            }
            
            return $suggestions;
        } catch (\Exception $e) {
            Log::error("테이블 최적화 제안 조회 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return [
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 모니터링 데이터를 캐싱합니다.
     * 
     * @param array $data 모니터링 데이터
     * @return bool 성공 여부
     */
    protected function cacheMonitoringData(array $data)
    {
        try {
            // 이전 모니터링 데이터 가져오기
            $history = Cache::get($this->cacheKey, []);
            
            // 최대 24개 항목 유지 (24시간)
            if (count($history) >= 24) {
                array_shift($history);
            }
            
            // 새 데이터 추가
            $history[] = [
                'timestamp' => $data['timestamp'],
                'connection_status' => $data['connection_status'],
                'tables_count' => $data['tables']['count'],
                'queries' => $data['queries'],
                'slow_queries_count' => $data['slow_queries']['count'] ?? 0
            ];
            
            // 캐싱
            Cache::put($this->cacheKey, $history, $this->cacheTtl);
            
            return true;
        } catch (\Exception $e) {
            Log::error("모니터링 데이터 캐싱 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return false;
        }
    }
    
    /**
     * 모니터링 데이터 기록을 가져옵니다.
     * 
     * @return array 모니터링 데이터 기록
     */
    public function getMonitoringHistory()
    {
        try {
            $history = Cache::get($this->cacheKey, []);
            
            return [
                'count' => count($history),
                'history' => $history
            ];
        } catch (\Exception $e) {
            Log::error("모니터링 기록 조회 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return [
                'count' => 0,
                'history' => [],
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 바이트 크기를 사람이 읽기 쉬운 형식으로 변환합니다.
     * 
     * @param int $bytes 바이트 크기
     * @param int $precision 소수점 자릿수
     * @return string 변환된 크기
     */
    protected function formatBytes(int $bytes, int $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
} 