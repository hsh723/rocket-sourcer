<?php

namespace App\Services\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

/**
 * 데이터베이스 연결 풀 관리자
 * 
 * 데이터베이스 연결 풀을 관리하고 최적화하는 서비스입니다.
 * 연결 풀 크기 조정, 연결 상태 모니터링, 연결 재사용 등의 기능을 제공합니다.
 */
class ConnectionPoolManager
{
    /**
     * 기본 연결 풀 설정
     * 
     * @var array
     */
    protected $defaultPoolSettings = [
        'min_connections' => 5,
        'max_connections' => 20,
        'idle_timeout' => 600, // 10분
        'max_lifetime' => 3600, // 1시간
        'connection_timeout' => 5, // 5초
        'validation_interval' => 30 // 30초
    ];
    
    /**
     * 현재 연결 풀 설정
     * 
     * @var array
     */
    protected $poolSettings = [];
    
    /**
     * 활성 연결 추적
     * 
     * @var array
     */
    protected $activeConnections = [];
    
    /**
     * 연결 통계
     * 
     * @var array
     */
    protected $connectionStats = [
        'created' => 0,
        'released' => 0,
        'failed' => 0,
        'max_active' => 0,
        'wait_time_total' => 0,
        'wait_count' => 0
    ];
    
    /**
     * 생성자
     * 
     * @param array $settings 연결 풀 설정
     */
    public function __construct(array $settings = [])
    {
        $this->poolSettings = array_merge($this->defaultPoolSettings, $settings);
        $this->initializeConnectionPool();
    }
    
    /**
     * 연결 풀을 초기화합니다.
     * 
     * @return void
     */
    protected function initializeConnectionPool()
    {
        try {
            // 기존 연결 설정 백업
            $originalConfig = Config::get('database.connections.' . Config::get('database.default'));
            
            // 연결 풀 설정 적용
            $poolConfig = array_merge($originalConfig, [
                'options' => [
                    \PDO::ATTR_PERSISTENT => true, // 영구 연결 활성화
                ]
            ]);
            
            // 최소 연결 수 만큼 미리 연결 생성
            for ($i = 0; $i < $this->poolSettings['min_connections']; $i++) {
                $this->createConnection();
            }
            
            Log::info("데이터베이스 연결 풀 초기화 완료", [
                'min_connections' => $this->poolSettings['min_connections'],
                'max_connections' => $this->poolSettings['max_connections']
            ]);
        } catch (\Exception $e) {
            Log::error("연결 풀 초기화 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
        }
    }
    
    /**
     * 새 연결을 생성합니다.
     * 
     * @return \PDO|null 연결 객체 또는 null
     */
    protected function createConnection()
    {
        try {
            $startTime = microtime(true);
            
            // 연결 생성
            $connection = DB::connection()->getPdo();
            
            $endTime = microtime(true);
            $connectionTime = ($endTime - $startTime) * 1000; // 밀리초
            
            // 연결 정보 저장
            $connectionId = spl_object_hash($connection);
            $this->activeConnections[$connectionId] = [
                'connection' => $connection,
                'created_at' => time(),
                'last_used_at' => time(),
                'is_active' => false,
                'connection_time_ms' => $connectionTime
            ];
            
            // 통계 업데이트
            $this->connectionStats['created']++;
            
            Log::debug("데이터베이스 연결 생성됨", [
                'connection_id' => $connectionId,
                'connection_time_ms' => $connectionTime
            ]);
            
            return $connection;
        } catch (\Exception $e) {
            $this->connectionStats['failed']++;
            
            Log::error("연결 생성 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return null;
        }
    }
    
    /**
     * 연결 풀에서 연결을 가져옵니다.
     * 
     * @return \PDO|null 연결 객체 또는 null
     */
    public function getConnection()
    {
        $startTime = microtime(true);
        
        try {
            // 사용 가능한 유휴 연결 찾기
            foreach ($this->activeConnections as $connectionId => &$connectionInfo) {
                if (!$connectionInfo['is_active']) {
                    // 연결이 오래되었는지 확인
                    $lifetime = time() - $connectionInfo['created_at'];
                    if ($lifetime > $this->poolSettings['max_lifetime']) {
                        // 오래된 연결 제거
                        $this->closeConnection($connectionId);
                        continue;
                    }
                    
                    // 유휴 시간이 너무 길었는지 확인
                    $idleTime = time() - $connectionInfo['last_used_at'];
                    if ($idleTime > $this->poolSettings['idle_timeout']) {
                        // 유휴 연결 제거
                        $this->closeConnection($connectionId);
                        continue;
                    }
                    
                    // 연결 유효성 검사
                    if (!$this->validateConnection($connectionInfo['connection'])) {
                        $this->closeConnection($connectionId);
                        continue;
                    }
                    
                    // 사용 가능한 연결 반환
                    $connectionInfo['is_active'] = true;
                    $connectionInfo['last_used_at'] = time();
                    
                    $endTime = microtime(true);
                    $waitTime = ($endTime - $startTime) * 1000; // 밀리초
                    
                    $this->connectionStats['wait_time_total'] += $waitTime;
                    $this->connectionStats['wait_count']++;
                    
                    $activeCount = $this->getActiveConnectionCount();
                    if ($activeCount > $this->connectionStats['max_active']) {
                        $this->connectionStats['max_active'] = $activeCount;
                    }
                    
                    Log::debug("기존 연결 재사용", [
                        'connection_id' => $connectionId,
                        'wait_time_ms' => $waitTime,
                        'active_connections' => $activeCount
                    ]);
                    
                    return $connectionInfo['connection'];
                }
            }
            
            // 사용 가능한 연결이 없고 최대 연결 수에 도달하지 않은 경우 새 연결 생성
            $activeCount = $this->getActiveConnectionCount();
            if ($activeCount < $this->poolSettings['max_connections']) {
                $connection = $this->createConnection();
                
                if ($connection) {
                    $connectionId = spl_object_hash($connection);
                    $this->activeConnections[$connectionId]['is_active'] = true;
                    
                    $endTime = microtime(true);
                    $waitTime = ($endTime - $startTime) * 1000; // 밀리초
                    
                    $this->connectionStats['wait_time_total'] += $waitTime;
                    $this->connectionStats['wait_count']++;
                    
                    $activeCount = $this->getActiveConnectionCount();
                    if ($activeCount > $this->connectionStats['max_active']) {
                        $this->connectionStats['max_active'] = $activeCount;
                    }
                    
                    Log::debug("새 연결 생성 및 사용", [
                        'connection_id' => $connectionId,
                        'wait_time_ms' => $waitTime,
                        'active_connections' => $activeCount
                    ]);
                    
                    return $connection;
                }
            }
            
            // 최대 연결 수에 도달한 경우 대기 후 재시도
            $retryCount = 0;
            $maxRetries = 3;
            
            while ($retryCount < $maxRetries) {
                // 잠시 대기
                usleep(100000); // 0.1초
                $retryCount++;
                
                // 사용 가능한 연결 다시 확인
                foreach ($this->activeConnections as $connectionId => &$connectionInfo) {
                    if (!$connectionInfo['is_active']) {
                        $connectionInfo['is_active'] = true;
                        $connectionInfo['last_used_at'] = time();
                        
                        $endTime = microtime(true);
                        $waitTime = ($endTime - $startTime) * 1000; // 밀리초
                        
                        $this->connectionStats['wait_time_total'] += $waitTime;
                        $this->connectionStats['wait_count']++;
                        
                        Log::debug("대기 후 연결 획득", [
                            'connection_id' => $connectionId,
                            'wait_time_ms' => $waitTime,
                            'retry_count' => $retryCount
                        ]);
                        
                        return $connectionInfo['connection'];
                    }
                }
            }
            
            // 연결을 얻지 못한 경우
            Log::warning("연결 풀 고갈: 사용 가능한 연결 없음", [
                'active_connections' => $this->getActiveConnectionCount(),
                'max_connections' => $this->poolSettings['max_connections'],
                'wait_time_ms' => (microtime(true) - $startTime) * 1000
            ]);
            
            return null;
        } catch (\Exception $e) {
            Log::error("연결 획득 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return null;
        }
    }
    
    /**
     * 연결을 풀로 반환합니다.
     * 
     * @param \PDO $connection 연결 객체
     * @return bool 성공 여부
     */
    public function releaseConnection($connection)
    {
        try {
            $connectionId = spl_object_hash($connection);
            
            if (isset($this->activeConnections[$connectionId])) {
                $this->activeConnections[$connectionId]['is_active'] = false;
                $this->activeConnections[$connectionId]['last_used_at'] = time();
                
                $this->connectionStats['released']++;
                
                Log::debug("연결 반환됨", [
                    'connection_id' => $connectionId
                ]);
                
                return true;
            }
            
            Log::warning("알 수 없는 연결 반환 시도", [
                'connection_id' => $connectionId
            ]);
            
            return false;
        } catch (\Exception $e) {
            Log::error("연결 반환 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return false;
        }
    }
    
    /**
     * 연결을 닫고 풀에서 제거합니다.
     * 
     * @param string $connectionId 연결 ID
     * @return bool 성공 여부
     */
    protected function closeConnection(string $connectionId)
    {
        try {
            if (isset($this->activeConnections[$connectionId])) {
                $connection = $this->activeConnections[$connectionId]['connection'];
                
                // 연결 닫기 시도
                try {
                    $connection = null; // 참조 해제
                } catch (\Exception $e) {
                    Log::warning("연결 닫기 오류: {$e->getMessage()}", [
                        'connection_id' => $connectionId
                    ]);
                }
                
                // 연결 정보 제거
                unset($this->activeConnections[$connectionId]);
                
                Log::debug("연결 닫힘", [
                    'connection_id' => $connectionId
                ]);
                
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error("연결 닫기 오류: {$e->getMessage()}", [
                'connection_id' => $connectionId,
                'exception' => $e
            ]);
            
            return false;
        }
    }
    
    /**
     * 연결이 유효한지 확인합니다.
     * 
     * @param \PDO $connection 연결 객체
     * @return bool 유효 여부
     */
    protected function validateConnection($connection)
    {
        try {
            // 간단한 쿼리로 연결 상태 확인
            $connection->query('SELECT 1');
            return true;
        } catch (\Exception $e) {
            Log::warning("연결 유효성 검사 실패: {$e->getMessage()}");
            return false;
        }
    }
    
    /**
     * 활성 연결 수를 가져옵니다.
     * 
     * @return int 활성 연결 수
     */
    public function getActiveConnectionCount()
    {
        $activeCount = 0;
        
        foreach ($this->activeConnections as $connectionInfo) {
            if ($connectionInfo['is_active']) {
                $activeCount++;
            }
        }
        
        return $activeCount;
    }
    
    /**
     * 총 연결 수를 가져옵니다.
     * 
     * @return int 총 연결 수
     */
    public function getTotalConnectionCount()
    {
        return count($this->activeConnections);
    }
    
    /**
     * 연결 풀 상태를 가져옵니다.
     * 
     * @return array 연결 풀 상태
     */
    public function getPoolStatus()
    {
        $activeCount = $this->getActiveConnectionCount();
        $totalCount = $this->getTotalConnectionCount();
        $idleCount = $totalCount - $activeCount;
        
        $avgWaitTime = $this->connectionStats['wait_count'] > 0
            ? $this->connectionStats['wait_time_total'] / $this->connectionStats['wait_count']
            : 0;
        
        return [
            'active_connections' => $activeCount,
            'idle_connections' => $idleCount,
            'total_connections' => $totalCount,
            'max_connections' => $this->poolSettings['max_connections'],
            'min_connections' => $this->poolSettings['min_connections'],
            'utilization_percent' => $this->poolSettings['max_connections'] > 0
                ? round(($activeCount / $this->poolSettings['max_connections']) * 100, 2)
                : 0,
            'stats' => [
                'created' => $this->connectionStats['created'],
                'released' => $this->connectionStats['released'],
                'failed' => $this->connectionStats['failed'],
                'max_active' => $this->connectionStats['max_active'],
                'avg_wait_time_ms' => round($avgWaitTime, 2)
            ],
            'settings' => $this->poolSettings
        ];
    }
    
    /**
     * 연결 풀 설정을 업데이트합니다.
     * 
     * @param array $settings 새 설정
     * @return bool 성공 여부
     */
    public function updatePoolSettings(array $settings)
    {
        try {
            $oldSettings = $this->poolSettings;
            $this->poolSettings = array_merge($this->poolSettings, $settings);
            
            // 최소 연결 수가 증가한 경우 추가 연결 생성
            if ($this->poolSettings['min_connections'] > $oldSettings['min_connections']) {
                $currentCount = $this->getTotalConnectionCount();
                $neededConnections = max(0, $this->poolSettings['min_connections'] - $currentCount);
                
                for ($i = 0; $i < $neededConnections; $i++) {
                    $this->createConnection();
                }
            }
            
            Log::info("연결 풀 설정 업데이트됨", [
                'old_settings' => $oldSettings,
                'new_settings' => $this->poolSettings
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error("연결 풀 설정 업데이트 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return false;
        }
    }
    
    /**
     * 연결 풀을 정리합니다.
     * 
     * @return array 정리 결과
     */
    public function pruneConnections()
    {
        $result = [
            'idle_closed' => 0,
            'expired_closed' => 0,
            'invalid_closed' => 0,
            'total_closed' => 0,
            'remaining_connections' => 0
        ];
        
        try {
            $now = time();
            $minConnectionsToKeep = $this->poolSettings['min_connections'];
            $currentTotal = $this->getTotalConnectionCount();
            
            // 최소 연결 수 이상인 경우에만 정리 수행
            if ($currentTotal <= $minConnectionsToKeep) {
                $result['remaining_connections'] = $currentTotal;
                return $result;
            }
            
            // 연결 ID 목록 복사 (반복 중 삭제를 위해)
            $connectionIds = array_keys($this->activeConnections);
            
            foreach ($connectionIds as $connectionId) {
                // 이미 제거된 경우 스킵
                if (!isset($this->activeConnections[$connectionId])) {
                    continue;
                }
                
                $connectionInfo = $this->activeConnections[$connectionId];
                
                // 활성 연결은 건너뜀
                if ($connectionInfo['is_active']) {
                    continue;
                }
                
                // 최대 수명 초과 확인
                $lifetime = $now - $connectionInfo['created_at'];
                if ($lifetime > $this->poolSettings['max_lifetime']) {
                    $this->closeConnection($connectionId);
                    $result['expired_closed']++;
                    $result['total_closed']++;
                    continue;
                }
                
                // 유휴 시간 초과 확인
                $idleTime = $now - $connectionInfo['last_used_at'];
                if ($idleTime > $this->poolSettings['idle_timeout']) {
                    $this->closeConnection($connectionId);
                    $result['idle_closed']++;
                    $result['total_closed']++;
                    continue;
                }
                
                // 연결 유효성 검사
                if (!$this->validateConnection($connectionInfo['connection'])) {
                    $this->closeConnection($connectionId);
                    $result['invalid_closed']++;
                    $result['total_closed']++;
                    continue;
                }
                
                // 최소 연결 수 확인
                $remainingConnections = $this->getTotalConnectionCount();
                if ($remainingConnections <= $minConnectionsToKeep) {
                    break;
                }
            }
            
            $result['remaining_connections'] = $this->getTotalConnectionCount();
            
            Log::info("연결 풀 정리 완료", $result);
            
            return $result;
        } catch (\Exception $e) {
            Log::error("연결 풀 정리 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            $result['error'] = $e->getMessage();
            $result['remaining_connections'] = $this->getTotalConnectionCount();
            
            return $result;
        }
    }
    
    /**
     * 연결 풀을 종료합니다.
     * 
     * @return bool 성공 여부
     */
    public function shutdown()
    {
        try {
            $connectionIds = array_keys($this->activeConnections);
            $closedCount = 0;
            
            foreach ($connectionIds as $connectionId) {
                if ($this->closeConnection($connectionId)) {
                    $closedCount++;
                }
            }
            
            Log::info("연결 풀 종료됨", [
                'closed_connections' => $closedCount,
                'total_connections' => count($connectionIds)
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error("연결 풀 종료 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return false;
        }
    }
} 