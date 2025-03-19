<?php

namespace App\Services\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

/**
 * 데이터베이스 복제 관리자
 * 
 * 데이터베이스 복제(Replication) 설정 및 관리를 위한 서비스입니다.
 * 마스터-슬레이브 복제 설정, 상태 모니터링, 장애 조치 등의 기능을 제공합니다.
 */
class ReplicationManager
{
    /**
     * 마스터 연결 이름
     * 
     * @var string
     */
    protected $masterConnection;
    
    /**
     * 슬레이브 연결 이름 목록
     * 
     * @var array
     */
    protected $slaveConnections = [];
    
    /**
     * 복제 상태 확인 간격 (초)
     * 
     * @var int
     */
    protected $checkInterval = 60;
    
    /**
     * 마지막 상태 확인 시간
     * 
     * @var Carbon|null
     */
    protected $lastCheckTime = null;
    
    /**
     * 생성자
     * 
     * @param string $masterConnection 마스터 연결 이름
     * @param array $slaveConnections 슬레이브 연결 이름 목록
     */
    public function __construct(string $masterConnection = 'mysql', array $slaveConnections = [])
    {
        $this->masterConnection = $masterConnection;
        $this->slaveConnections = $slaveConnections;
        
        // 기본 슬레이브 연결이 없는 경우 설정에서 찾기
        if (empty($this->slaveConnections)) {
            $this->detectSlaveConnections();
        }
    }
    
    /**
     * 설정에서 슬레이브 연결을 찾습니다.
     * 
     * @return void
     */
    protected function detectSlaveConnections()
    {
        $connections = Config::get('database.connections', []);
        
        foreach ($connections as $name => $config) {
            // 이름에 'slave' 또는 'replica'가 포함된 연결 찾기
            if ($name !== $this->masterConnection && 
                (strpos($name, 'slave') !== false || strpos($name, 'replica') !== false)) {
                $this->slaveConnections[] = $name;
            }
        }
        
        Log::info("슬레이브 연결 자동 감지됨", [
            'master' => $this->masterConnection,
            'slaves' => $this->slaveConnections
        ]);
    }
    
    /**
     * 복제 상태를 확인합니다.
     * 
     * @param bool $force 강제 확인 여부
     * @return array 복제 상태
     */
    public function checkReplicationStatus(bool $force = false)
    {
        $now = Carbon::now();
        
        // 마지막 확인 이후 충분한 시간이 지나지 않은 경우 스킵
        if (!$force && $this->lastCheckTime && $now->diffInSeconds($this->lastCheckTime) < $this->checkInterval) {
            return [
                'message' => "마지막 확인 이후 {$this->checkInterval}초가 지나지 않았습니다.",
                'last_check' => $this->lastCheckTime->toDateTimeString(),
                'next_check' => $this->lastCheckTime->addSeconds($this->checkInterval)->toDateTimeString()
            ];
        }
        
        $this->lastCheckTime = $now;
        
        try {
            $masterStatus = $this->getMasterStatus();
            $slavesStatus = [];
            
            foreach ($this->slaveConnections as $slave) {
                $slavesStatus[$slave] = $this->getSlaveStatus($slave);
            }
            
            $result = [
                'timestamp' => $now->toDateTimeString(),
                'master' => [
                    'connection' => $this->masterConnection,
                    'status' => $masterStatus
                ],
                'slaves' => []
            ];
            
            foreach ($slavesStatus as $slave => $status) {
                $result['slaves'][$slave] = [
                    'connection' => $slave,
                    'status' => $status
                ];
                
                // 복제 지연 계산
                if (isset($status['Seconds_Behind_Master'])) {
                    $result['slaves'][$slave]['lag_seconds'] = (int) $status['Seconds_Behind_Master'];
                }
                
                // 복제 상태 확인
                if (isset($status['Slave_IO_Running']) && isset($status['Slave_SQL_Running'])) {
                    $ioRunning = strtolower($status['Slave_IO_Running']) === 'yes';
                    $sqlRunning = strtolower($status['Slave_SQL_Running']) === 'yes';
                    
                    $result['slaves'][$slave]['is_running'] = $ioRunning && $sqlRunning;
                    $result['slaves'][$slave]['health'] = $this->calculateSlaveHealth($status);
                }
            }
            
            // 전체 복제 상태 요약
            $result['summary'] = $this->summarizeReplicationStatus($result);
            
            Log::info("복제 상태 확인 완료", [
                'master' => $this->masterConnection,
                'slaves_count' => count($this->slaveConnections),
                'summary' => $result['summary']
            ]);
            
            return $result;
        } catch (\Exception $e) {
            Log::error("복제 상태 확인 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return [
                'error' => $e->getMessage(),
                'timestamp' => $now->toDateTimeString()
            ];
        }
    }
    
    /**
     * 마스터 상태를 가져옵니다.
     * 
     * @return array 마스터 상태
     */
    protected function getMasterStatus()
    {
        try {
            $result = DB::connection($this->masterConnection)
                ->select('SHOW MASTER STATUS');
            
            return !empty($result) ? (array) $result[0] : [];
        } catch (\Exception $e) {
            Log::error("마스터 상태 조회 오류: {$e->getMessage()}", [
                'connection' => $this->masterConnection,
                'exception' => $e
            ]);
            
            return [
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 슬레이브 상태를 가져옵니다.
     * 
     * @param string $connection 연결 이름
     * @return array 슬레이브 상태
     */
    protected function getSlaveStatus(string $connection)
    {
        try {
            $result = DB::connection($connection)
                ->select('SHOW SLAVE STATUS');
            
            return !empty($result) ? (array) $result[0] : [];
        } catch (\Exception $e) {
            Log::error("슬레이브 상태 조회 오류: {$e->getMessage()}", [
                'connection' => $connection,
                'exception' => $e
            ]);
            
            return [
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 슬레이브 상태를 기반으로 건강 상태를 계산합니다.
     * 
     * @param array $status 슬레이브 상태
     * @return string 건강 상태 (healthy, warning, critical)
     */
    protected function calculateSlaveHealth(array $status)
    {
        // 오류가 있는 경우
        if (isset($status['error'])) {
            return 'critical';
        }
        
        // IO 또는 SQL 스레드가 실행 중이 아닌 경우
        if (isset($status['Slave_IO_Running']) && strtolower($status['Slave_IO_Running']) !== 'yes') {
            return 'critical';
        }
        
        if (isset($status['Slave_SQL_Running']) && strtolower($status['Slave_SQL_Running']) !== 'yes') {
            return 'critical';
        }
        
        // 복제 지연이 큰 경우
        if (isset($status['Seconds_Behind_Master'])) {
            $lag = (int) $status['Seconds_Behind_Master'];
            
            if ($lag > 300) { // 5분 이상 지연
                return 'critical';
            } else if ($lag > 60) { // 1분 이상 지연
                return 'warning';
            }
        }
        
        // 마지막 오류가 있는 경우
        if (isset($status['Last_Error']) && !empty($status['Last_Error'])) {
            return 'warning';
        }
        
        return 'healthy';
    }
    
    /**
     * 전체 복제 상태를 요약합니다.
     * 
     * @param array $status 복제 상태
     * @return array 요약 정보
     */
    protected function summarizeReplicationStatus(array $status)
    {
        $summary = [
            'total_slaves' => count($this->slaveConnections),
            'healthy_slaves' => 0,
            'warning_slaves' => 0,
            'critical_slaves' => 0,
            'max_lag_seconds' => 0,
            'overall_health' => 'healthy'
        ];
        
        if (empty($status['slaves'])) {
            $summary['overall_health'] = 'unknown';
            return $summary;
        }
        
        foreach ($status['slaves'] as $slave) {
            if (isset($slave['health'])) {
                switch ($slave['health']) {
                    case 'healthy':
                        $summary['healthy_slaves']++;
                        break;
                    case 'warning':
                        $summary['warning_slaves']++;
                        break;
                    case 'critical':
                        $summary['critical_slaves']++;
                        break;
                }
            }
            
            if (isset($slave['lag_seconds']) && $slave['lag_seconds'] > $summary['max_lag_seconds']) {
                $summary['max_lag_seconds'] = $slave['lag_seconds'];
            }
        }
        
        // 전체 건강 상태 결정
        if ($summary['critical_slaves'] > 0) {
            $summary['overall_health'] = 'critical';
        } else if ($summary['warning_slaves'] > 0) {
            $summary['overall_health'] = 'warning';
        }
        
        return $summary;
    }
    
    /**
     * 슬레이브 복제를 시작합니다.
     * 
     * @param string $connection 연결 이름
     * @return array 실행 결과
     */
    public function startSlave(string $connection)
    {
        try {
            if (!in_array($connection, $this->slaveConnections)) {
                throw new \Exception("유효하지 않은 슬레이브 연결: {$connection}");
            }
            
            DB::connection($connection)->statement('START SLAVE');
            
            Log::info("슬레이브 복제 시작됨", [
                'connection' => $connection
            ]);
            
            // 상태 확인
            $status = $this->getSlaveStatus($connection);
            
            return [
                'success' => true,
                'connection' => $connection,
                'status' => $status
            ];
        } catch (\Exception $e) {
            Log::error("슬레이브 시작 오류: {$e->getMessage()}", [
                'connection' => $connection,
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'connection' => $connection,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 슬레이브 복제를 중지합니다.
     * 
     * @param string $connection 연결 이름
     * @return array 실행 결과
     */
    public function stopSlave(string $connection)
    {
        try {
            if (!in_array($connection, $this->slaveConnections)) {
                throw new \Exception("유효하지 않은 슬레이브 연결: {$connection}");
            }
            
            DB::connection($connection)->statement('STOP SLAVE');
            
            Log::info("슬레이브 복제 중지됨", [
                'connection' => $connection
            ]);
            
            // 상태 확인
            $status = $this->getSlaveStatus($connection);
            
            return [
                'success' => true,
                'connection' => $connection,
                'status' => $status
            ];
        } catch (\Exception $e) {
            Log::error("슬레이브 중지 오류: {$e->getMessage()}", [
                'connection' => $connection,
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'connection' => $connection,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 슬레이브 복제를 재설정합니다.
     * 
     * @param string $connection 연결 이름
     * @return array 실행 결과
     */
    public function resetSlave(string $connection)
    {
        try {
            if (!in_array($connection, $this->slaveConnections)) {
                throw new \Exception("유효하지 않은 슬레이브 연결: {$connection}");
            }
            
            // 슬레이브 중지
            DB::connection($connection)->statement('STOP SLAVE');
            
            // 슬레이브 재설정
            DB::connection($connection)->statement('RESET SLAVE');
            
            // 슬레이브 시작
            DB::connection($connection)->statement('START SLAVE');
            
            Log::info("슬레이브 복제 재설정됨", [
                'connection' => $connection
            ]);
            
            // 상태 확인
            $status = $this->getSlaveStatus($connection);
            
            return [
                'success' => true,
                'connection' => $connection,
                'status' => $status
            ];
        } catch (\Exception $e) {
            Log::error("슬레이브 재설정 오류: {$e->getMessage()}", [
                'connection' => $connection,
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'connection' => $connection,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 슬레이브 복제 위치를 변경합니다.
     * 
     * @param string $connection 연결 이름
     * @param string $masterHost 마스터 호스트
     * @param int $masterPort 마스터 포트
     * @param string $masterUser 마스터 사용자
     * @param string $masterPassword 마스터 비밀번호
     * @param string $logFile 로그 파일
     * @param int $logPos 로그 위치
     * @return array 실행 결과
     */
    public function changeMaster(
        string $connection,
        string $masterHost,
        int $masterPort,
        string $masterUser,
        string $masterPassword,
        string $logFile,
        int $logPos
    ) {
        try {
            if (!in_array($connection, $this->slaveConnections)) {
                throw new \Exception("유효하지 않은 슬레이브 연결: {$connection}");
            }
            
            // 슬레이브 중지
            DB::connection($connection)->statement('STOP SLAVE');
            
            // 마스터 변경
            $query = "CHANGE MASTER TO 
                MASTER_HOST = '{$masterHost}',
                MASTER_PORT = {$masterPort},
                MASTER_USER = '{$masterUser}',
                MASTER_PASSWORD = '{$masterPassword}',
                MASTER_LOG_FILE = '{$logFile}',
                MASTER_LOG_POS = {$logPos}";
            
            DB::connection($connection)->statement($query);
            
            // 슬레이브 시작
            DB::connection($connection)->statement('START SLAVE');
            
            Log::info("슬레이브 마스터 변경됨", [
                'connection' => $connection,
                'master_host' => $masterHost,
                'master_port' => $masterPort
            ]);
            
            // 상태 확인
            $status = $this->getSlaveStatus($connection);
            
            return [
                'success' => true,
                'connection' => $connection,
                'status' => $status
            ];
        } catch (\Exception $e) {
            Log::error("마스터 변경 오류: {$e->getMessage()}", [
                'connection' => $connection,
                'master_host' => $masterHost,
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'connection' => $connection,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 복제 오류를 건너뜁니다.
     * 
     * @param string $connection 연결 이름
     * @return array 실행 결과
     */
    public function skipReplicationError(string $connection)
    {
        try {
            if (!in_array($connection, $this->slaveConnections)) {
                throw new \Exception("유효하지 않은 슬레이브 연결: {$connection}");
            }
            
            // 슬레이브 상태 확인
            $status = $this->getSlaveStatus($connection);
            
            // 오류가 없는 경우
            if (empty($status['Last_Error'])) {
                return [
                    'success' => true,
                    'connection' => $connection,
                    'message' => '건너뛸 오류가 없습니다.',
                    'status' => $status
                ];
            }
            
            // 슬레이브 중지
            DB::connection($connection)->statement('STOP SLAVE');
            
            // 오류 건너뛰기
            DB::connection($connection)->statement('SET GLOBAL SQL_SLAVE_SKIP_COUNTER = 1');
            
            // 슬레이브 시작
            DB::connection($connection)->statement('START SLAVE');
            
            Log::info("슬레이브 복제 오류 건너뜀", [
                'connection' => $connection,
                'error' => $status['Last_Error']
            ]);
            
            // 상태 확인
            $newStatus = $this->getSlaveStatus($connection);
            
            return [
                'success' => true,
                'connection' => $connection,
                'skipped_error' => $status['Last_Error'],
                'status' => $newStatus
            ];
        } catch (\Exception $e) {
            Log::error("오류 건너뛰기 실패: {$e->getMessage()}", [
                'connection' => $connection,
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'connection' => $connection,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 복제 지연을 모니터링합니다.
     * 
     * @param int $warningThreshold 경고 임계값 (초)
     * @param int $criticalThreshold 심각 임계값 (초)
     * @return array 모니터링 결과
     */
    public function monitorReplicationLag(int $warningThreshold = 60, int $criticalThreshold = 300)
    {
        try {
            $status = $this->checkReplicationStatus(true);
            $result = [
                'timestamp' => Carbon::now()->toDateTimeString(),
                'thresholds' => [
                    'warning' => $warningThreshold,
                    'critical' => $criticalThreshold
                ],
                'slaves' => []
            ];
            
            if (!isset($status['slaves'])) {
                throw new \Exception("슬레이브 상태를 가져올 수 없습니다.");
            }
            
            foreach ($status['slaves'] as $slave => $slaveStatus) {
                $lag = $slaveStatus['lag_seconds'] ?? null;
                $health = 'healthy';
                
                if ($lag !== null) {
                    if ($lag >= $criticalThreshold) {
                        $health = 'critical';
                    } else if ($lag >= $warningThreshold) {
                        $health = 'warning';
                    }
                } else {
                    $health = 'unknown';
                }
                
                $result['slaves'][$slave] = [
                    'lag_seconds' => $lag,
                    'health' => $health
                ];
            }
            
            // 전체 상태 결정
            $result['overall_health'] = 'healthy';
            
            foreach ($result['slaves'] as $slave) {
                if ($slave['health'] === 'critical') {
                    $result['overall_health'] = 'critical';
                    break;
                } else if ($slave['health'] === 'warning' && $result['overall_health'] !== 'critical') {
                    $result['overall_health'] = 'warning';
                }
            }
            
            Log::info("복제 지연 모니터링 완료", [
                'overall_health' => $result['overall_health'],
                'max_lag' => $status['summary']['max_lag_seconds'] ?? 0
            ]);
            
            return $result;
        } catch (\Exception $e) {
            Log::error("복제 지연 모니터링 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return [
                'error' => $e->getMessage(),
                'timestamp' => Carbon::now()->toDateTimeString()
            ];
        }
    }
    
    /**
     * 복제 설정을 가져옵니다.
     * 
     * @return array 복제 설정
     */
    public function getReplicationConfig()
    {
        return [
            'master_connection' => $this->masterConnection,
            'slave_connections' => $this->slaveConnections,
            'check_interval' => $this->checkInterval,
            'last_check_time' => $this->lastCheckTime ? $this->lastCheckTime->toDateTimeString() : null
        ];
    }
    
    /**
     * 복제 설정을 업데이트합니다.
     * 
     * @param array $config 설정 배열
     * @return array 업데이트 결과
     */
    public function updateConfig(array $config)
    {
        try {
            $updated = [];
            
            if (isset($config['master_connection'])) {
                $oldMaster = $this->masterConnection;
                $this->masterConnection = $config['master_connection'];
                $updated['master_connection'] = ['old' => $oldMaster, 'new' => $this->masterConnection];
            }
            
            if (isset($config['slave_connections'])) {
                $oldSlaves = $this->slaveConnections;
                $this->slaveConnections = $config['slave_connections'];
                $updated['slave_connections'] = ['old' => $oldSlaves, 'new' => $this->slaveConnections];
            }
            
            if (isset($config['check_interval'])) {
                $oldInterval = $this->checkInterval;
                $this->checkInterval = $config['check_interval'];
                $updated['check_interval'] = ['old' => $oldInterval, 'new' => $this->checkInterval];
            }
            
            Log::info("복제 설정 업데이트됨", $updated);
            
            return [
                'success' => true,
                'updated' => $updated,
                'current_config' => $this->getReplicationConfig()
            ];
        } catch (\Exception $e) {
            Log::error("복제 설정 업데이트 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
} 