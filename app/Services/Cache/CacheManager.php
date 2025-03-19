<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

/**
 * 캐시 관리자
 * 
 * 애플리케이션 전체의 캐시를 관리하고 최적화하는 서비스입니다.
 * 캐시 정리, 모니터링, 분석 및 최적화 기능을 제공합니다.
 */
class CacheManager
{
    /**
     * 캐시 그룹 정의
     */
    const GROUP_PRODUCT = 'product';
    const GROUP_USER = 'user';
    const GROUP_RECOMMENDATION = 'recommendation';
    const GROUP_CATEGORY = 'category';
    const GROUP_STATS = 'stats';
    const GROUP_SYSTEM = 'system';
    
    /**
     * 캐시 그룹별 TTL (초)
     * 
     * @var array
     */
    protected $groupTtl = [
        self::GROUP_PRODUCT => 3600, // 1시간
        self::GROUP_USER => 86400, // 1일
        self::GROUP_RECOMMENDATION => 43200, // 12시간
        self::GROUP_CATEGORY => 86400, // 1일
        self::GROUP_STATS => 300, // 5분
        self::GROUP_SYSTEM => 604800, // 1주일
    ];
    
    /**
     * 캐시 그룹별 접두사
     * 
     * @var array
     */
    protected $groupPrefix = [
        self::GROUP_PRODUCT => 'prod:',
        self::GROUP_USER => 'user:',
        self::GROUP_RECOMMENDATION => 'rec:',
        self::GROUP_CATEGORY => 'cat:',
        self::GROUP_STATS => 'stats:',
        self::GROUP_SYSTEM => 'sys:',
    ];
    
    /**
     * 고급 캐싱 서비스 인스턴스
     * 
     * @var AdvancedCacheService
     */
    protected $cacheService;
    
    /**
     * 캐시 사용량 제한 (바이트)
     * 
     * @var int
     */
    protected $memoryLimit = 100 * 1024 * 1024; // 100MB
    
    /**
     * 생성자
     * 
     * @param AdvancedCacheService $cacheService
     */
    public function __construct(AdvancedCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }
    
    /**
     * 특정 그룹의 모든 캐시를 삭제합니다.
     * 
     * @param string $group 캐시 그룹
     * @return bool 성공 여부
     */
    public function clearGroup(string $group)
    {
        if (!$this->isValidGroup($group)) {
            Log::warning("유효하지 않은 캐시 그룹: {$group}");
            return false;
        }
        
        try {
            $prefix = $this->groupPrefix[$group];
            return $this->cacheService->forgetByPrefix($prefix);
        } catch (\Exception $e) {
            Log::error("캐시 그룹 삭제 오류: {$e->getMessage()}", [
                'group' => $group,
                'exception' => $e
            ]);
            
            return false;
        }
    }
    
    /**
     * 모든 캐시를 삭제합니다.
     * 
     * @return bool 성공 여부
     */
    public function clearAll()
    {
        try {
            Cache::flush();
            Log::info("모든 캐시가 삭제되었습니다.");
            return true;
        } catch (\Exception $e) {
            Log::error("모든 캐시 삭제 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return false;
        }
    }
    
    /**
     * 특정 엔티티와 관련된 모든 캐시를 삭제합니다.
     * 
     * @param string $entityType 엔티티 유형 (product, user, category 등)
     * @param int $entityId 엔티티 ID
     * @return bool 성공 여부
     */
    public function clearEntity(string $entityType, int $entityId)
    {
        try {
            $group = $this->getGroupForEntityType($entityType);
            
            if (!$group) {
                Log::warning("유효하지 않은 엔티티 유형: {$entityType}");
                return false;
            }
            
            $prefix = $this->groupPrefix[$group] . $entityId . ':';
            return $this->cacheService->forgetByPrefix($prefix);
        } catch (\Exception $e) {
            Log::error("엔티티 캐시 삭제 오류: {$e->getMessage()}", [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'exception' => $e
            ]);
            
            return false;
        }
    }
    
    /**
     * 오래된 캐시를 정리합니다.
     * 
     * @param int $olderThanDays 지정된 일수보다 오래된 캐시 삭제
     * @return array 정리 결과
     */
    public function pruneStaleCache(int $olderThanDays = 7)
    {
        try {
            $result = [
                'scanned' => 0,
                'deleted' => 0,
                'errors' => 0,
                'groups' => []
            ];
            
            // Redis 캐시 드라이버인 경우
            if (config('cache.default') === 'redis') {
                $redis = Redis::connection(config('cache.stores.redis.connection'));
                
                foreach ($this->groupPrefix as $group => $prefix) {
                    $fullPrefix = $this->cacheService->buildKey($prefix) . '*';
                    $keys = $redis->keys($fullPrefix);
                    
                    $result['scanned'] += count($keys);
                    $result['groups'][$group] = [
                        'scanned' => count($keys),
                        'deleted' => 0
                    ];
                    
                    foreach ($keys as $key) {
                        // TTL 확인
                        $ttl = $redis->ttl($key);
                        
                        // TTL이 없거나 지정된 일수보다 오래된 경우
                        if ($ttl < 0 || $ttl > ($olderThanDays * 86400)) {
                            $redis->del($key);
                            $result['deleted']++;
                            $result['groups'][$group]['deleted']++;
                        }
                    }
                }
            } else {
                // 다른 드라이버의 경우 경고 로그 기록
                Log::warning("현재 캐시 드라이버는 오래된 캐시 정리를 지원하지 않습니다.", [
                    'driver' => config('cache.default')
                ]);
                
                return [
                    'error' => '현재 캐시 드라이버는 오래된 캐시 정리를 지원하지 않습니다.',
                    'driver' => config('cache.default')
                ];
            }
            
            Log::info("오래된 캐시 정리 완료", $result);
            return $result;
        } catch (\Exception $e) {
            Log::error("오래된 캐시 정리 오류: {$e->getMessage()}", [
                'older_than_days' => $olderThanDays,
                'exception' => $e
            ]);
            
            return [
                'error' => $e->getMessage(),
                'scanned' => 0,
                'deleted' => 0,
                'errors' => 1
            ];
        }
    }
    
    /**
     * 캐시 사용량 통계를 가져옵니다.
     * 
     * @return array 캐시 사용량 통계
     */
    public function getUsageStats()
    {
        try {
            $stats = [
                'total_keys' => 0,
                'memory_usage' => 0,
                'hit_rate' => 0,
                'groups' => []
            ];
            
            // Redis 캐시 드라이버인 경우
            if (config('cache.default') === 'redis') {
                $redis = Redis::connection(config('cache.stores.redis.connection'));
                
                // 전체 메모리 사용량
                $info = $redis->info('memory');
                $stats['memory_usage'] = $info['used_memory'] ?? 0;
                $stats['memory_usage_human'] = $this->formatBytes($stats['memory_usage']);
                $stats['memory_limit'] = $this->memoryLimit;
                $stats['memory_limit_human'] = $this->formatBytes($this->memoryLimit);
                $stats['memory_usage_percent'] = $this->memoryLimit > 0 
                    ? round(($stats['memory_usage'] / $this->memoryLimit) * 100, 2) 
                    : 0;
                
                // 그룹별 통계
                foreach ($this->groupPrefix as $group => $prefix) {
                    $fullPrefix = $this->cacheService->buildKey($prefix) . '*';
                    $keys = $redis->keys($fullPrefix);
                    
                    $groupStats = [
                        'keys' => count($keys),
                        'size' => 0,
                        'size_human' => '0 B',
                        'ttl_avg' => 0
                    ];
                    
                    $totalTtl = 0;
                    
                    foreach ($keys as $key) {
                        // 키 크기 계산
                        $type = $redis->type($key);
                        $size = 0;
                        
                        switch ($type) {
                            case 'string':
                                $value = $redis->get($key);
                                $size = strlen($key) + strlen($value);
                                break;
                            case 'hash':
                                $values = $redis->hGetAll($key);
                                $size = strlen($key);
                                foreach ($values as $field => $value) {
                                    $size += strlen($field) + strlen($value);
                                }
                                break;
                            case 'list':
                                $length = $redis->lLen($key);
                                $size = strlen($key);
                                for ($i = 0; $i < $length; $i++) {
                                    $value = $redis->lIndex($key, $i);
                                    $size += strlen($value);
                                }
                                break;
                            case 'set':
                                $members = $redis->sMembers($key);
                                $size = strlen($key);
                                foreach ($members as $member) {
                                    $size += strlen($member);
                                }
                                break;
                            case 'zset':
                                $members = $redis->zRange($key, 0, -1, true);
                                $size = strlen($key);
                                foreach ($members as $member => $score) {
                                    $size += strlen($member) + 8; // 8 bytes for score
                                }
                                break;
                        }
                        
                        $groupStats['size'] += $size;
                        
                        // TTL 계산
                        $ttl = $redis->ttl($key);
                        if ($ttl > 0) {
                            $totalTtl += $ttl;
                        }
                    }
                    
                    $groupStats['size_human'] = $this->formatBytes($groupStats['size']);
                    $groupStats['ttl_avg'] = $groupStats['keys'] > 0 ? round($totalTtl / $groupStats['keys']) : 0;
                    
                    $stats['groups'][$group] = $groupStats;
                    $stats['total_keys'] += $groupStats['keys'];
                }
                
                // 캐시 적중률
                $cacheStats = $this->cacheService->getStats();
                $hits = $cacheStats['hits'] ?? 0;
                $misses = $cacheStats['misses'] ?? 0;
                $total = $hits + $misses;
                $stats['hit_rate'] = $total > 0 ? round(($hits / $total) * 100, 2) : 0;
                $stats['hits'] = $hits;
                $stats['misses'] = $misses;
            } else {
                // 다른 드라이버의 경우 경고 로그 기록
                Log::warning("현재 캐시 드라이버는 상세 사용량 통계를 지원하지 않습니다.", [
                    'driver' => config('cache.default')
                ]);
                
                // 기본 통계만 반환
                $cacheStats = $this->cacheService->getStats();
                $hits = $cacheStats['hits'] ?? 0;
                $misses = $cacheStats['misses'] ?? 0;
                $total = $hits + $misses;
                $stats['hit_rate'] = $total > 0 ? round(($hits / $total) * 100, 2) : 0;
                $stats['hits'] = $hits;
                $stats['misses'] = $misses;
                $stats['driver_limited'] = true;
            }
            
            return $stats;
        } catch (\Exception $e) {
            Log::error("캐시 사용량 통계 조회 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return [
                'error' => $e->getMessage(),
                'total_keys' => 0,
                'memory_usage' => 0,
                'hit_rate' => 0
            ];
        }
    }
    
    /**
     * 캐시 최적화를 수행합니다.
     * 
     * @return array 최적화 결과
     */
    public function optimize()
    {
        try {
            $result = [
                'actions' => [],
                'memory_before' => 0,
                'memory_after' => 0,
                'keys_removed' => 0
            ];
            
            // 현재 메모리 사용량 확인
            $stats = $this->getUsageStats();
            $result['memory_before'] = $stats['memory_usage'] ?? 0;
            
            // 1. 오래된 캐시 정리
            $pruneResult = $this->pruneStaleCache(30); // 30일 이상 된 캐시 정리
            $result['actions'][] = [
                'action' => 'prune_stale',
                'deleted' => $pruneResult['deleted'] ?? 0
            ];
            $result['keys_removed'] += $pruneResult['deleted'] ?? 0;
            
            // 2. 적중률이 낮은 캐시 정리
            $lowHitRateGroups = [];
            foreach ($stats['groups'] ?? [] as $group => $groupStats) {
                // 그룹별 적중률 계산 (임의의 계산 방식)
                $hitRate = $this->calculateGroupHitRate($group);
                
                // 적중률이 30% 미만인 그룹 정리
                if ($hitRate < 30) {
                    $this->clearGroup($group);
                    $lowHitRateGroups[] = $group;
                    $result['keys_removed'] += $groupStats['keys'] ?? 0;
                }
            }
            
            if (!empty($lowHitRateGroups)) {
                $result['actions'][] = [
                    'action' => 'clear_low_hit_rate',
                    'groups' => $lowHitRateGroups
                ];
            }
            
            // 3. 메모리 사용량이 제한에 가까운 경우 추가 정리
            if (($stats['memory_usage'] ?? 0) > ($this->memoryLimit * 0.8)) {
                // 통계 캐시 정리 (일반적으로 재생성이 쉬운 데이터)
                $this->clearGroup(self::GROUP_STATS);
                $result['actions'][] = [
                    'action' => 'clear_stats_for_memory',
                    'group' => self::GROUP_STATS,
                    'keys_removed' => $stats['groups'][self::GROUP_STATS]['keys'] ?? 0
                ];
                $result['keys_removed'] += $stats['groups'][self::GROUP_STATS]['keys'] ?? 0;
            }
            
            // 최적화 후 메모리 사용량 확인
            $statsAfter = $this->getUsageStats();
            $result['memory_after'] = $statsAfter['memory_usage'] ?? 0;
            $result['memory_saved'] = $result['memory_before'] - $result['memory_after'];
            $result['memory_saved_human'] = $this->formatBytes($result['memory_saved']);
            $result['memory_saved_percent'] = $result['memory_before'] > 0 
                ? round(($result['memory_saved'] / $result['memory_before']) * 100, 2) 
                : 0;
            
            Log::info("캐시 최적화 완료", $result);
            return $result;
        } catch (\Exception $e) {
            Log::error("캐시 최적화 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return [
                'error' => $e->getMessage(),
                'actions' => [],
                'memory_saved' => 0,
                'keys_removed' => 0
            ];
        }
    }
    
    /**
     * 특정 그룹의 TTL을 설정합니다.
     * 
     * @param string $group 캐시 그룹
     * @param int $ttl TTL (초)
     * @return bool 성공 여부
     */
    public function setGroupTtl(string $group, int $ttl)
    {
        if (!$this->isValidGroup($group)) {
            Log::warning("유효하지 않은 캐시 그룹: {$group}");
            return false;
        }
        
        $this->groupTtl[$group] = $ttl;
        return true;
    }
    
    /**
     * 특정 그룹의 TTL을 가져옵니다.
     * 
     * @param string $group 캐시 그룹
     * @return int|null TTL (초) 또는 null
     */
    public function getGroupTtl(string $group)
    {
        if (!$this->isValidGroup($group)) {
            Log::warning("유효하지 않은 캐시 그룹: {$group}");
            return null;
        }
        
        return $this->groupTtl[$group];
    }
    
    /**
     * 캐시 메모리 제한을 설정합니다.
     * 
     * @param int $limitBytes 메모리 제한 (바이트)
     * @return $this
     */
    public function setMemoryLimit(int $limitBytes)
    {
        $this->memoryLimit = $limitBytes;
        return $this;
    }
    
    /**
     * 캐시 그룹이 유효한지 확인합니다.
     * 
     * @param string $group 캐시 그룹
     * @return bool 유효 여부
     */
    protected function isValidGroup(string $group)
    {
        return isset($this->groupPrefix[$group]);
    }
    
    /**
     * 엔티티 유형에 해당하는 캐시 그룹을 가져옵니다.
     * 
     * @param string $entityType 엔티티 유형
     * @return string|null 캐시 그룹 또는 null
     */
    protected function getGroupForEntityType(string $entityType)
    {
        $mapping = [
            'product' => self::GROUP_PRODUCT,
            'user' => self::GROUP_USER,
            'category' => self::GROUP_CATEGORY,
            'recommendation' => self::GROUP_RECOMMENDATION,
            'stats' => self::GROUP_STATS,
            'system' => self::GROUP_SYSTEM
        ];
        
        return $mapping[$entityType] ?? null;
    }
    
    /**
     * 그룹별 적중률을 계산합니다.
     * 
     * @param string $group 캐시 그룹
     * @return float 적중률 (%)
     */
    protected function calculateGroupHitRate(string $group)
    {
        // 실제 구현에서는 그룹별 적중률을 추적하는 로직 필요
        // 현재는 임의의 값 반환
        $cacheStats = $this->cacheService->getStats();
        $keys = $cacheStats['keys'] ?? [];
        
        $groupHits = 0;
        $groupTotal = 0;
        
        foreach ($keys as $key => $count) {
            if (Str::startsWith($key, $this->groupPrefix[$group])) {
                $groupTotal += $count;
                $groupHits += $count * 0.8; // 임의의 적중률 계산
            }
        }
        
        return $groupTotal > 0 ? round(($groupHits / $groupTotal) * 100, 2) : 50;
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