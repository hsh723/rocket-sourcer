<?php

namespace App\Services\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;

/**
 * 데이터베이스 트랜잭션 관리자
 * 
 * 데이터베이스 트랜잭션을 관리하고 모니터링하는 서비스입니다.
 * 중첩 트랜잭션, 트랜잭션 로깅, 트랜잭션 타임아웃 등의 기능을 제공합니다.
 */
class TransactionManager
{
    /**
     * 활성 트랜잭션 목록
     * 
     * @var array
     */
    protected $activeTransactions = [];
    
    /**
     * 트랜잭션 ID 카운터
     * 
     * @var int
     */
    protected $transactionCounter = 0;
    
    /**
     * 트랜잭션 로깅 활성화 여부
     * 
     * @var bool
     */
    protected $loggingEnabled = false;
    
    /**
     * 트랜잭션 타임아웃 (초)
     * 
     * @var int|null
     */
    protected $transactionTimeout = null;
    
    /**
     * 트랜잭션 통계
     * 
     * @var array
     */
    protected $stats = [
        'started' => 0,
        'committed' => 0,
        'rolledBack' => 0,
        'errors' => 0,
        'timeouts' => 0
    ];
    
    /**
     * 생성자
     */
    public function __construct()
    {
        // 트랜잭션 타임아웃 설정
        $this->transactionTimeout = config('database.transaction_timeout', null);
    }
    
    /**
     * 트랜잭션 로깅 활성화 여부를 설정합니다.
     * 
     * @param bool $enabled 활성화 여부
     * @return $this
     */
    public function setLoggingEnabled(bool $enabled)
    {
        $this->loggingEnabled = $enabled;
        return $this;
    }
    
    /**
     * 트랜잭션 타임아웃을 설정합니다.
     * 
     * @param int|null $seconds 초 단위 타임아웃 (null이면 타임아웃 없음)
     * @return $this
     */
    public function setTransactionTimeout(?int $seconds)
    {
        $this->transactionTimeout = $seconds;
        return $this;
    }
    
    /**
     * 트랜잭션을 시작합니다.
     * 
     * @param string|null $connection 데이터베이스 연결 이름 (null이면 기본 연결)
     * @param string|null $name 트랜잭션 이름 (null이면 자동 생성)
     * @return array 트랜잭션 정보
     */
    public function begin(?string $connection = null, ?string $name = null)
    {
        $connection = $connection ?? config('database.default');
        $db = DB::connection($connection);
        
        // 트랜잭션 ID 생성
        $transactionId = ++$this->transactionCounter;
        $name = $name ?? 'transaction_' . $transactionId;
        
        // 트랜잭션 시작
        $db->beginTransaction();
        
        // 트랜잭션 정보 저장
        $transaction = [
            'id' => $transactionId,
            'name' => $name,
            'connection' => $connection,
            'start_time' => microtime(true),
            'timeout' => $this->transactionTimeout,
            'level' => $db->transactionLevel(),
            'queries' => [],
            'status' => 'active'
        ];
        
        $this->activeTransactions[$transactionId] = $transaction;
        $this->stats['started']++;
        
        if ($this->loggingEnabled) {
            Log::info("트랜잭션 시작", [
                'transaction_id' => $transactionId,
                'name' => $name,
                'connection' => $connection,
                'level' => $transaction['level']
            ]);
        }
        
        return $transaction;
    }
    
    /**
     * 트랜잭션을 커밋합니다.
     * 
     * @param int $transactionId 트랜잭션 ID
     * @return bool 커밋 성공 여부
     */
    public function commit(int $transactionId)
    {
        if (!isset($this->activeTransactions[$transactionId])) {
            Log::warning("존재하지 않는 트랜잭션 커밋 시도", [
                'transaction_id' => $transactionId
            ]);
            
            return false;
        }
        
        $transaction = $this->activeTransactions[$transactionId];
        $connection = $transaction['connection'];
        $db = DB::connection($connection);
        
        try {
            // 트랜잭션 타임아웃 확인
            if ($this->isTransactionTimedOut($transaction)) {
                $this->rollback($transactionId);
                $this->stats['timeouts']++;
                
                throw new \Exception("트랜잭션 타임아웃: {$transaction['name']}");
            }
            
            // 트랜잭션 커밋
            $db->commit();
            
            // 트랜잭션 정보 업데이트
            $transaction['end_time'] = microtime(true);
            $transaction['duration'] = $transaction['end_time'] - $transaction['start_time'];
            $transaction['status'] = 'committed';
            
            $this->stats['committed']++;
            
            if ($this->loggingEnabled) {
                Log::info("트랜잭션 커밋", [
                    'transaction_id' => $transactionId,
                    'name' => $transaction['name'],
                    'connection' => $connection,
                    'duration' => $transaction['duration'],
                    'queries_count' => count($transaction['queries'])
                ]);
            }
            
            // 트랜잭션 레벨이 0이면 활성 트랜잭션에서 제거
            if ($db->transactionLevel() === 0) {
                unset($this->activeTransactions[$transactionId]);
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error("트랜잭션 커밋 오류: {$e->getMessage()}", [
                'transaction_id' => $transactionId,
                'name' => $transaction['name'],
                'connection' => $connection,
                'exception' => $e
            ]);
            
            $this->stats['errors']++;
            
            // 오류 발생 시 롤백 시도
            try {
                $this->rollback($transactionId);
            } catch (\Exception $rollbackException) {
                Log::error("트랜잭션 롤백 오류: {$rollbackException->getMessage()}", [
                    'transaction_id' => $transactionId,
                    'name' => $transaction['name'],
                    'connection' => $connection,
                    'exception' => $rollbackException
                ]);
            }
            
            throw $e;
        }
    }
    
    /**
     * 트랜잭션을 롤백합니다.
     * 
     * @param int $transactionId 트랜잭션 ID
     * @return bool 롤백 성공 여부
     */
    public function rollback(int $transactionId)
    {
        if (!isset($this->activeTransactions[$transactionId])) {
            Log::warning("존재하지 않는 트랜잭션 롤백 시도", [
                'transaction_id' => $transactionId
            ]);
            
            return false;
        }
        
        $transaction = $this->activeTransactions[$transactionId];
        $connection = $transaction['connection'];
        $db = DB::connection($connection);
        
        try {
            // 트랜잭션 롤백
            $db->rollBack();
            
            // 트랜잭션 정보 업데이트
            $transaction['end_time'] = microtime(true);
            $transaction['duration'] = $transaction['end_time'] - $transaction['start_time'];
            $transaction['status'] = 'rolled_back';
            
            $this->stats['rolledBack']++;
            
            if ($this->loggingEnabled) {
                Log::info("트랜잭션 롤백", [
                    'transaction_id' => $transactionId,
                    'name' => $transaction['name'],
                    'connection' => $connection,
                    'duration' => $transaction['duration'],
                    'queries_count' => count($transaction['queries'])
                ]);
            }
            
            // 트랜잭션 레벨이 0이면 활성 트랜잭션에서 제거
            if ($db->transactionLevel() === 0) {
                unset($this->activeTransactions[$transactionId]);
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error("트랜잭션 롤백 오류: {$e->getMessage()}", [
                'transaction_id' => $transactionId,
                'name' => $transaction['name'],
                'connection' => $connection,
                'exception' => $e
            ]);
            
            $this->stats['errors']++;
            
            throw $e;
        }
    }
    
    /**
     * 트랜잭션 내에서 콜백을 실행합니다.
     * 
     * @param callable $callback 실행할 콜백
     * @param string|null $connection 데이터베이스 연결 이름 (null이면 기본 연결)
     * @param string|null $name 트랜잭션 이름 (null이면 자동 생성)
     * @param int $attempts 재시도 횟수
     * @return mixed 콜백 실행 결과
     */
    public function transaction(callable $callback, ?string $connection = null, ?string $name = null, int $attempts = 1)
    {
        $transaction = $this->begin($connection, $name);
        $transactionId = $transaction['id'];
        
        try {
            // 콜백 실행
            $result = $callback($transaction);
            
            // 성공 시 커밋
            $this->commit($transactionId);
            
            return $result;
        } catch (\Exception $e) {
            // 오류 발생 시 롤백
            $this->rollback($transactionId);
            
            // 재시도 횟수가 남아있으면 재시도
            if ($attempts > 1) {
                return $this->transaction($callback, $connection, $name, $attempts - 1);
            }
            
            throw $e;
        }
    }
    
    /**
     * 트랜잭션에 쿼리를 기록합니다.
     * 
     * @param int $transactionId 트랜잭션 ID
     * @param string $query 쿼리
     * @param array $bindings 쿼리 바인딩
     * @param float $time 쿼리 실행 시간 (초)
     * @return void
     */
    public function logQuery(int $transactionId, string $query, array $bindings, float $time)
    {
        if (!isset($this->activeTransactions[$transactionId])) {
            return;
        }
        
        $this->activeTransactions[$transactionId]['queries'][] = [
            'query' => $query,
            'bindings' => $bindings,
            'time' => $time,
            'timestamp' => microtime(true)
        ];
    }
    
    /**
     * 활성 트랜잭션 목록을 가져옵니다.
     * 
     * @return array 활성 트랜잭션 목록
     */
    public function getActiveTransactions()
    {
        // 트랜잭션 타임아웃 확인
        foreach ($this->activeTransactions as $id => $transaction) {
            if ($this->isTransactionTimedOut($transaction)) {
                $this->activeTransactions[$id]['status'] = 'timed_out';
            }
        }
        
        return $this->activeTransactions;
    }
    
    /**
     * 트랜잭션 통계를 가져옵니다.
     * 
     * @return array 트랜잭션 통계
     */
    public function getStats()
    {
        return [
            'started' => $this->stats['started'],
            'committed' => $this->stats['committed'],
            'rolled_back' => $this->stats['rolledBack'],
            'errors' => $this->stats['errors'],
            'timeouts' => $this->stats['timeouts'],
            'active' => count($this->activeTransactions),
            'success_rate' => $this->stats['started'] > 0 
                ? round(($this->stats['committed'] / $this->stats['started']) * 100, 2) 
                : 0
        ];
    }
    
    /**
     * 트랜잭션이 타임아웃되었는지 확인합니다.
     * 
     * @param array $transaction 트랜잭션 정보
     * @return bool 타임아웃 여부
     */
    protected function isTransactionTimedOut(array $transaction)
    {
        if ($transaction['timeout'] === null) {
            return false;
        }
        
        $currentTime = microtime(true);
        $duration = $currentTime - $transaction['start_time'];
        
        return $duration > $transaction['timeout'];
    }
    
    /**
     * 장시간 실행 중인 트랜잭션을 감지합니다.
     * 
     * @param float $thresholdSeconds 임계값 (초)
     * @return array 장시간 실행 중인 트랜잭션 목록
     */
    public function detectLongRunningTransactions(float $thresholdSeconds = 30.0)
    {
        $longRunning = [];
        $currentTime = microtime(true);
        
        foreach ($this->activeTransactions as $id => $transaction) {
            $duration = $currentTime - $transaction['start_time'];
            
            if ($duration > $thresholdSeconds) {
                $transaction['current_duration'] = $duration;
                $longRunning[$id] = $transaction;
            }
        }
        
        if ($this->loggingEnabled && !empty($longRunning)) {
            Log::warning("장시간 실행 중인 트랜잭션 감지", [
                'count' => count($longRunning),
                'threshold_seconds' => $thresholdSeconds,
                'transactions' => array_keys($longRunning)
            ]);
        }
        
        return $longRunning;
    }
    
    /**
     * 장시간 실행 중인 트랜잭션을 강제로 롤백합니다.
     * 
     * @param float $thresholdSeconds 임계값 (초)
     * @return int 롤백된 트랜잭션 수
     */
    public function forceRollbackLongRunningTransactions(float $thresholdSeconds = 60.0)
    {
        $longRunning = $this->detectLongRunningTransactions($thresholdSeconds);
        $rolledBackCount = 0;
        
        foreach ($longRunning as $id => $transaction) {
            try {
                $this->rollback($id);
                $rolledBackCount++;
                
                Log::warning("장시간 실행 중인 트랜잭션 강제 롤백", [
                    'transaction_id' => $id,
                    'name' => $transaction['name'],
                    'connection' => $transaction['connection'],
                    'duration' => $transaction['current_duration']
                ]);
            } catch (\Exception $e) {
                Log::error("장시간 실행 중인 트랜잭션 롤백 오류: {$e->getMessage()}", [
                    'transaction_id' => $id,
                    'name' => $transaction['name'],
                    'connection' => $transaction['connection'],
                    'exception' => $e
                ]);
            }
        }
        
        return $rolledBackCount;
    }
    
    /**
     * 데이터베이스 연결에 트랜잭션 이벤트 리스너를 등록합니다.
     * 
     * @param string|null $connection 데이터베이스 연결 이름 (null이면 기본 연결)
     * @return void
     */
    public function registerTransactionEvents(?string $connection = null)
    {
        $connection = $connection ?? config('database.default');
        $db = DB::connection($connection);
        
        // 이벤트 리스너 등록
        $db->beforeExecuting(function ($query, $bindings, $connection) {
            // 현재 활성 트랜잭션이 있는지 확인
            if (empty($this->activeTransactions)) {
                return;
            }
            
            // 가장 최근 트랜잭션 ID 찾기
            $transactionId = max(array_keys($this->activeTransactions));
            
            // 쿼리 시작 시간 기록
            $this->activeTransactions[$transactionId]['last_query_start'] = microtime(true);
        });
        
        $db->afterExecuting(function ($query, $bindings, $time, $connection) {
            // 현재 활성 트랜잭션이 있는지 확인
            if (empty($this->activeTransactions)) {
                return;
            }
            
            // 가장 최근 트랜잭션 ID 찾기
            $transactionId = max(array_keys($this->activeTransactions));
            
            // 쿼리 로깅
            $this->logQuery($transactionId, $query, $bindings, $time);
        });
    }
    
    /**
     * 모든 활성 트랜잭션을 롤백합니다.
     * 
     * @return int 롤백된 트랜잭션 수
     */
    public function rollbackAll()
    {
        $rolledBackCount = 0;
        
        foreach (array_keys($this->activeTransactions) as $id) {
            try {
                $this->rollback($id);
                $rolledBackCount++;
            } catch (\Exception $e) {
                Log::error("트랜잭션 롤백 오류: {$e->getMessage()}", [
                    'transaction_id' => $id,
                    'exception' => $e
                ]);
            }
        }
        
        return $rolledBackCount;
    }
} 