<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * 고급 캐싱 서비스
 * 
 * 다양한 캐싱 전략과 자동 무효화 기능을 제공하는 고급 캐싱 서비스입니다.
 * 계층적 캐싱, 태그 기반 캐싱, 자동 무효화, 캐시 통계 등의 기능을 제공합니다.
 */
class AdvancedCacheService
{
    /**
     * 캐시 저장소
     * 
     * @var string
     */
    protected $store;
    
    /**
     * 기본 캐시 만료 시간 (초)
     * 
     * @var int
     */
    protected $defaultTtl = 3600;
    
    /**
     * 캐시 접두사
     * 
     * @var string
     */
    protected $prefix = 'adv_cache:';
    
    /**
     * 캐시 통계 활성화 여부
     * 
     * @var bool
     */
    protected $statsEnabled = true;
    
    /**
     * 캐시 통계 키
     * 
     * @var string
     */
    protected $statsKey = 'cache_stats';
    
    /**
     * 생성자
     * 
     * @param string|null $store 캐시 저장소
     * @param int|null $defaultTtl 기본 캐시 만료 시간
     * @param string|null $prefix 캐시 접두사
     */
    public function __construct(string $store = null, int $defaultTtl = null, string $prefix = null)
    {
        $this->store = $store ?: config('cache.default');
        
        if ($defaultTtl !== null) {
            $this->defaultTtl = $defaultTtl;
        }
        
        if ($prefix !== null) {
            $this->prefix = $prefix;
        }
    }
    
    /**
     * 캐시에서 값을 가져옵니다.
     * 
     * @param string $key 캐시 키
     * @param mixed $default 기본값
     * @param array $tags 캐시 태그
     * @return mixed 캐시된 값 또는 기본값
     */
    public function get(string $key, $default = null, array $tags = [])
    {
        $cacheKey = $this->buildKey($key);
        
        try {
            $cache = $this->getCache($tags);
            $value = $cache->get($cacheKey);
            
            if ($value === null) {
                $this->recordStat('miss', $key);
                return $default;
            }
            
            $this->recordStat('hit', $key);
            return $value;
        } catch (\Exception $e) {
            Log::error("캐시 조회 오류: {$e->getMessage()}", [
                'key' => $key,
                'tags' => $tags,
                'exception' => $e
            ]);
            
            return $default;
        }
    }
    
    /**
     * 캐시에 값을 저장합니다.
     * 
     * @param string $key 캐시 키
     * @param mixed $value 저장할 값
     * @param int|null $ttl 캐시 만료 시간 (초)
     * @param array $tags 캐시 태그
     * @return bool 성공 여부
     */
    public function put(string $key, $value, int $ttl = null, array $tags = [])
    {
        $cacheKey = $this->buildKey($key);
        $ttl = $ttl ?: $this->defaultTtl;
        
        try {
            $cache = $this->getCache($tags);
            $result = $cache->put($cacheKey, $value, $ttl);
            
            $this->recordStat('set', $key);
            return $result;
        } catch (\Exception $e) {
            Log::error("캐시 저장 오류: {$e->getMessage()}", [
                'key' => $key,
                'tags' => $tags,
                'ttl' => $ttl,
                'exception' => $e
            ]);
            
            return false;
        }
    }
    
    /**
     * 캐시에서 값을 가져오거나, 없으면 콜백 함수를 실행하여 저장합니다.
     * 
     * @param string $key 캐시 키
     * @param int|null $ttl 캐시 만료 시간 (초)
     * @param callable $callback 콜백 함수
     * @param array $tags 캐시 태그
     * @return mixed 캐시된 값 또는 콜백 결과
     */
    public function remember(string $key, int $ttl = null, callable $callback, array $tags = [])
    {
        $cacheKey = $this->buildKey($key);
        $ttl = $ttl ?: $this->defaultTtl;
        
        try {
            $cache = $this->getCache($tags);
            
            if ($cache->has($cacheKey)) {
                $this->recordStat('hit', $key);
                return $cache->get($cacheKey);
            }
            
            $value = $callback();
            $cache->put($cacheKey, $value, $ttl);
            
            $this->recordStat('miss', $key);
            $this->recordStat('set', $key);
            
            return $value;
        } catch (\Exception $e) {
            Log::error("캐시 remember 오류: {$e->getMessage()}", [
                'key' => $key,
                'tags' => $tags,
                'ttl' => $ttl,
                'exception' => $e
            ]);
            
            // 오류 발생 시 콜백 실행 결과 반환
            return $callback();
        }
    }
    
    /**
     * 캐시에서 항목을 삭제합니다.
     * 
     * @param string $key 캐시 키
     * @param array $tags 캐시 태그
     * @return bool 성공 여부
     */
    public function forget(string $key, array $tags = [])
    {
        $cacheKey = $this->buildKey($key);
        
        try {
            $cache = $this->getCache($tags);
            $result = $cache->forget($cacheKey);
            
            $this->recordStat('delete', $key);
            return $result;
        } catch (\Exception $e) {
            Log::error("캐시 삭제 오류: {$e->getMessage()}", [
                'key' => $key,
                'tags' => $tags,
                'exception' => $e
            ]);
            
            return false;
        }
    }
    
    /**
     * 특정 태그가 있는 모든 캐시 항목을 삭제합니다.
     * 
     * @param array|string $tags 캐시 태그
     * @return bool 성공 여부
     */
    public function flushTags($tags)
    {
        if (!is_array($tags)) {
            $tags = [$tags];
        }
        
        try {
            $cache = Cache::store($this->store);
            
            if (method_exists($cache, 'tags')) {
                $cache->tags($tags)->flush();
                
                $this->recordStat('flush_tags', implode(',', $tags));
                return true;
            }
            
            Log::warning("현재 캐시 드라이버는 태그를 지원하지 않습니다.", [
                'store' => $this->store,
                'tags' => $tags
            ]);
            
            return false;
        } catch (\Exception $e) {
            Log::error("태그 기반 캐시 삭제 오류: {$e->getMessage()}", [
                'tags' => $tags,
                'exception' => $e
            ]);
            
            return false;
        }
    }
    
    /**
     * 계층적 캐시 키를 사용하여 캐시에서 값을 가져옵니다.
     * 
     * @param array $keyParts 캐시 키 부분
     * @param mixed $default 기본값
     * @param array $tags 캐시 태그
     * @return mixed 캐시된 값 또는 기본값
     */
    public function getHierarchical(array $keyParts, $default = null, array $tags = [])
    {
        $key = $this->buildHierarchicalKey($keyParts);
        return $this->get($key, $default, $tags);
    }
    
    /**
     * 계층적 캐시 키를 사용하여 캐시에 값을 저장합니다.
     * 
     * @param array $keyParts 캐시 키 부분
     * @param mixed $value 저장할 값
     * @param int|null $ttl 캐시 만료 시간 (초)
     * @param array $tags 캐시 태그
     * @return bool 성공 여부
     */
    public function putHierarchical(array $keyParts, $value, int $ttl = null, array $tags = [])
    {
        $key = $this->buildHierarchicalKey($keyParts);
        return $this->put($key, $value, $ttl, $tags);
    }
    
    /**
     * 계층적 캐시 키를 사용하여 캐시에서 값을 가져오거나, 없으면 콜백 함수를 실행하여 저장합니다.
     * 
     * @param array $keyParts 캐시 키 부분
     * @param int|null $ttl 캐시 만료 시간 (초)
     * @param callable $callback 콜백 함수
     * @param array $tags 캐시 태그
     * @return mixed 캐시된 값 또는 콜백 결과
     */
    public function rememberHierarchical(array $keyParts, int $ttl = null, callable $callback, array $tags = [])
    {
        $key = $this->buildHierarchicalKey($keyParts);
        return $this->remember($key, $ttl, $callback, $tags);
    }
    
    /**
     * 계층적 캐시 키를 사용하여 캐시에서 항목을 삭제합니다.
     * 
     * @param array $keyParts 캐시 키 부분
     * @param array $tags 캐시 태그
     * @return bool 성공 여부
     */
    public function forgetHierarchical(array $keyParts, array $tags = [])
    {
        $key = $this->buildHierarchicalKey($keyParts);
        return $this->forget($key, $tags);
    }
    
    /**
     * 특정 접두사로 시작하는 모든 캐시 항목을 삭제합니다.
     * 
     * @param string $prefix 캐시 키 접두사
     * @return bool 성공 여부
     */
    public function forgetByPrefix(string $prefix)
    {
        try {
            $fullPrefix = $this->buildKey($prefix);
            
            // Redis 캐시 드라이버인 경우
            if ($this->store === 'redis') {
                $redis = Cache::getRedis();
                $keys = $redis->keys("{$fullPrefix}*");
                
                if (!empty($keys)) {
                    $redis->del($keys);
                }
                
                $this->recordStat('forget_prefix', $prefix);
                return true;
            }
            
            // 다른 드라이버의 경우 경고 로그 기록
            Log::warning("현재 캐시 드라이버는 접두사 기반 삭제를 지원하지 않습니다.", [
                'store' => $this->store,
                'prefix' => $prefix
            ]);
            
            return false;
        } catch (\Exception $e) {
            Log::error("접두사 기반 캐시 삭제 오류: {$e->getMessage()}", [
                'prefix' => $prefix,
                'exception' => $e
            ]);
            
            return false;
        }
    }
    
    /**
     * 캐시 통계를 가져옵니다.
     * 
     * @return array 캐시 통계
     */
    public function getStats()
    {
        if (!$this->statsEnabled) {
            return [
                'enabled' => false,
                'message' => '캐시 통계가 비활성화되어 있습니다.'
            ];
        }
        
        try {
            $statsKey = $this->buildKey($this->statsKey);
            $stats = Cache::store($this->store)->get($statsKey, [
                'hits' => 0,
                'misses' => 0,
                'sets' => 0,
                'deletes' => 0,
                'flushes' => 0,
                'hit_rate' => 0,
                'keys' => [],
                'last_reset' => now()->toDateTimeString()
            ]);
            
            // 적중률 계산
            $total = $stats['hits'] + $stats['misses'];
            $stats['hit_rate'] = $total > 0 ? round(($stats['hits'] / $total) * 100, 2) : 0;
            
            return $stats;
        } catch (\Exception $e) {
            Log::error("캐시 통계 조회 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return [
                'enabled' => true,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 캐시 통계를 초기화합니다.
     * 
     * @return bool 성공 여부
     */
    public function resetStats()
    {
        if (!$this->statsEnabled) {
            return false;
        }
        
        try {
            $statsKey = $this->buildKey($this->statsKey);
            
            $stats = [
                'hits' => 0,
                'misses' => 0,
                'sets' => 0,
                'deletes' => 0,
                'flushes' => 0,
                'hit_rate' => 0,
                'keys' => [],
                'last_reset' => now()->toDateTimeString()
            ];
            
            Cache::store($this->store)->put($statsKey, $stats, 60 * 24 * 30); // 30일 유지
            
            return true;
        } catch (\Exception $e) {
            Log::error("캐시 통계 초기화 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return false;
        }
    }
    
    /**
     * 캐시 통계 활성화 여부를 설정합니다.
     * 
     * @param bool $enabled 활성화 여부
     * @return $this
     */
    public function setStatsEnabled(bool $enabled)
    {
        $this->statsEnabled = $enabled;
        return $this;
    }
    
    /**
     * 캐시 저장소를 설정합니다.
     * 
     * @param string $store 캐시 저장소
     * @return $this
     */
    public function setStore(string $store)
    {
        $this->store = $store;
        return $this;
    }
    
    /**
     * 기본 캐시 만료 시간을 설정합니다.
     * 
     * @param int $ttl 캐시 만료 시간 (초)
     * @return $this
     */
    public function setDefaultTtl(int $ttl)
    {
        $this->defaultTtl = $ttl;
        return $this;
    }
    
    /**
     * 캐시 접두사를 설정합니다.
     * 
     * @param string $prefix 캐시 접두사
     * @return $this
     */
    public function setPrefix(string $prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }
    
    /**
     * 캐시 키를 생성합니다.
     * 
     * @param string $key 원본 키
     * @return string 생성된 캐시 키
     */
    protected function buildKey(string $key)
    {
        return $this->prefix . $key;
    }
    
    /**
     * 계층적 캐시 키를 생성합니다.
     * 
     * @param array $keyParts 캐시 키 부분
     * @return string 생성된 캐시 키
     */
    protected function buildHierarchicalKey(array $keyParts)
    {
        return implode(':', array_map(function ($part) {
            return Str::slug($part, '_');
        }, $keyParts));
    }
    
    /**
     * 태그가 있는 캐시 인스턴스를 가져옵니다.
     * 
     * @param array $tags 캐시 태그
     * @return \Illuminate\Contracts\Cache\Repository 캐시 인스턴스
     */
    protected function getCache(array $tags = [])
    {
        $cache = Cache::store($this->store);
        
        if (!empty($tags) && method_exists($cache, 'tags')) {
            return $cache->tags($tags);
        }
        
        return $cache;
    }
    
    /**
     * 캐시 통계를 기록합니다.
     * 
     * @param string $type 통계 유형
     * @param string $key 캐시 키
     * @return void
     */
    protected function recordStat(string $type, string $key)
    {
        if (!$this->statsEnabled) {
            return;
        }
        
        try {
            $statsKey = $this->buildKey($this->statsKey);
            $stats = Cache::store($this->store)->get($statsKey, [
                'hits' => 0,
                'misses' => 0,
                'sets' => 0,
                'deletes' => 0,
                'flushes' => 0,
                'keys' => [],
                'last_reset' => now()->toDateTimeString()
            ]);
            
            switch ($type) {
                case 'hit':
                    $stats['hits']++;
                    break;
                case 'miss':
                    $stats['misses']++;
                    break;
                case 'set':
                    $stats['sets']++;
                    break;
                case 'delete':
                    $stats['deletes']++;
                    break;
                case 'flush_tags':
                case 'forget_prefix':
                    $stats['flushes']++;
                    break;
            }
            
            // 키 통계 업데이트 (최대 100개 키 유지)
            if (!isset($stats['keys'][$key])) {
                $stats['keys'][$key] = 0;
                
                if (count($stats['keys']) > 100) {
                    // 가장 적게 사용된 키 제거
                    asort($stats['keys']);
                    $stats['keys'] = array_slice($stats['keys'], 1, 100, true);
                }
            }
            
            $stats['keys'][$key]++;
            
            Cache::store($this->store)->put($statsKey, $stats, 60 * 24 * 30); // 30일 유지
        } catch (\Exception $e) {
            Log::error("캐시 통계 기록 오류: {$e->getMessage()}", [
                'type' => $type,
                'key' => $key,
                'exception' => $e
            ]);
        }
    }
} 