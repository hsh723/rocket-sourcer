<?php

namespace App\Services\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use App\Services\Cache\AdvancedCacheService;

/**
 * 데이터베이스 쿼리 캐싱 서비스
 * 
 * 데이터베이스 쿼리 결과를 캐싱하여 성능을 향상시키는 서비스입니다.
 * 자주 사용되는 쿼리나 변경이 적은 데이터에 대한 쿼리를 캐싱합니다.
 */
class QueryCache
{
    /**
     * 고급 캐시 서비스
     * 
     * @var AdvancedCacheService|null
     */
    protected $cacheService;
    
    /**
     * 캐시 접두사
     * 
     * @var string
     */
    protected $cachePrefix = 'db_query_';
    
    /**
     * 기본 캐시 유효 시간 (초)
     * 
     * @var int
     */
    protected $defaultTtl = 3600; // 1시간
    
    /**
     * 캐시 태그
     * 
     * @var string
     */
    protected $cacheTag = 'database_queries';
    
    /**
     * 캐시 활성화 여부
     * 
     * @var bool
     */
    protected $enabled = true;
    
    /**
     * 캐시 통계
     * 
     * @var array
     */
    protected $stats = [
        'hits' => 0,
        'misses' => 0,
        'stored' => 0,
        'flushed' => 0
    ];
    
    /**
     * 생성자
     * 
     * @param AdvancedCacheService|null $cacheService 고급 캐시 서비스
     */
    public function __construct(AdvancedCacheService $cacheService = null)
    {
        $this->cacheService = $cacheService;
    }
    
    /**
     * 캐시 활성화 여부를 설정합니다.
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
     * 기본 캐시 유효 시간을 설정합니다.
     * 
     * @param int $seconds 초 단위 유효 시간
     * @return $this
     */
    public function setDefaultTtl(int $seconds)
    {
        $this->defaultTtl = $seconds;
        return $this;
    }
    
    /**
     * 캐시 접두사를 설정합니다.
     * 
     * @param string $prefix 캐시 접두사
     * @return $this
     */
    public function setCachePrefix(string $prefix)
    {
        $this->cachePrefix = $prefix;
        return $this;
    }
    
    /**
     * 쿼리 결과를 캐싱합니다.
     * 
     * @param string|QueryBuilder|EloquentBuilder $query 쿼리 또는 쿼리 빌더
     * @param array $bindings 쿼리 바인딩
     * @param int|null $ttl 캐시 유효 시간 (초)
     * @param array $tags 추가 캐시 태그
     * @return mixed 쿼리 결과
     */
    public function remember($query, array $bindings = [], ?int $ttl = null, array $tags = [])
    {
        if (!$this->enabled) {
            return $this->executeQuery($query, $bindings);
        }
        
        $cacheKey = $this->generateCacheKey($query, $bindings);
        $ttl = $ttl ?? $this->defaultTtl;
        
        // 캐시 태그 설정
        $cacheTags = array_merge([$this->cacheTag], $tags);
        
        // 캐시 서비스 사용 여부에 따라 다른 방식으로 캐싱
        if ($this->cacheService) {
            $result = $this->cacheService->remember(
                $cacheKey,
                function () use ($query, $bindings) {
                    $this->stats['misses']++;
                    $this->stats['stored']++;
                    return $this->executeQuery($query, $bindings);
                },
                $ttl,
                $cacheTags
            );
            
            if (!$this->cacheService->wasRecentlyCreated($cacheKey)) {
                $this->stats['hits']++;
            }
            
            return $result;
        } else {
            // 기본 Laravel 캐시 사용
            if (empty($cacheTags)) {
                $result = Cache::remember($cacheKey, $ttl, function () use ($query, $bindings) {
                    $this->stats['misses']++;
                    $this->stats['stored']++;
                    return $this->executeQuery($query, $bindings);
                });
                
                if (Cache::has($cacheKey)) {
                    $this->stats['hits']++;
                }
                
                return $result;
            } else {
                $result = Cache::tags($cacheTags)->remember($cacheKey, $ttl, function () use ($query, $bindings) {
                    $this->stats['misses']++;
                    $this->stats['stored']++;
                    return $this->executeQuery($query, $bindings);
                });
                
                if (Cache::tags($cacheTags)->has($cacheKey)) {
                    $this->stats['hits']++;
                }
                
                return $result;
            }
        }
    }
    
    /**
     * 쿼리 결과를 영구적으로 캐싱합니다.
     * 
     * @param string|QueryBuilder|EloquentBuilder $query 쿼리 또는 쿼리 빌더
     * @param array $bindings 쿼리 바인딩
     * @param array $tags 추가 캐시 태그
     * @return mixed 쿼리 결과
     */
    public function rememberForever($query, array $bindings = [], array $tags = [])
    {
        return $this->remember($query, $bindings, null, $tags);
    }
    
    /**
     * 캐시된 쿼리 결과를 가져옵니다. 캐시가 없으면 null을 반환합니다.
     * 
     * @param string|QueryBuilder|EloquentBuilder $query 쿼리 또는 쿼리 빌더
     * @param array $bindings 쿼리 바인딩
     * @param array $tags 추가 캐시 태그
     * @return mixed|null 쿼리 결과 또는 null
     */
    public function get($query, array $bindings = [], array $tags = [])
    {
        if (!$this->enabled) {
            return null;
        }
        
        $cacheKey = $this->generateCacheKey($query, $bindings);
        $cacheTags = array_merge([$this->cacheTag], $tags);
        
        if ($this->cacheService) {
            $result = $this->cacheService->get($cacheKey);
            
            if ($result !== null) {
                $this->stats['hits']++;
            }
            
            return $result;
        } else {
            if (empty($cacheTags)) {
                $result = Cache::get($cacheKey);
                
                if ($result !== null) {
                    $this->stats['hits']++;
                }
                
                return $result;
            } else {
                $result = Cache::tags($cacheTags)->get($cacheKey);
                
                if ($result !== null) {
                    $this->stats['hits']++;
                }
                
                return $result;
            }
        }
    }
    
    /**
     * 캐시된 쿼리 결과를 삭제합니다.
     * 
     * @param string|QueryBuilder|EloquentBuilder $query 쿼리 또는 쿼리 빌더
     * @param array $bindings 쿼리 바인딩
     * @return bool 삭제 성공 여부
     */
    public function forget($query, array $bindings = [])
    {
        if (!$this->enabled) {
            return false;
        }
        
        $cacheKey = $this->generateCacheKey($query, $bindings);
        
        if ($this->cacheService) {
            $result = $this->cacheService->forget($cacheKey);
            
            if ($result) {
                $this->stats['flushed']++;
            }
            
            return $result;
        } else {
            $result = Cache::forget($cacheKey);
            
            if ($result) {
                $this->stats['flushed']++;
            }
            
            return $result;
        }
    }
    
    /**
     * 특정 태그와 관련된 모든 캐시를 삭제합니다.
     * 
     * @param array $tags 캐시 태그
     * @return bool 삭제 성공 여부
     */
    public function flushByTags(array $tags)
    {
        if (!$this->enabled) {
            return false;
        }
        
        $cacheTags = array_merge([$this->cacheTag], $tags);
        
        if ($this->cacheService) {
            $result = $this->cacheService->flushByTags($cacheTags);
            
            if ($result) {
                $this->stats['flushed'] += $result;
            }
            
            return $result > 0;
        } else {
            try {
                Cache::tags($cacheTags)->flush();
                $this->stats['flushed']++;
                return true;
            } catch (\Exception $e) {
                Log::error("캐시 태그 삭제 오류: {$e->getMessage()}", [
                    'tags' => $cacheTags,
                    'exception' => $e
                ]);
                
                return false;
            }
        }
    }
    
    /**
     * 모든 쿼리 캐시를 삭제합니다.
     * 
     * @return bool 삭제 성공 여부
     */
    public function flushAll()
    {
        if (!$this->enabled) {
            return false;
        }
        
        if ($this->cacheService) {
            $result = $this->cacheService->flushByTags([$this->cacheTag]);
            
            if ($result) {
                $this->stats['flushed'] += $result;
            }
            
            return $result > 0;
        } else {
            try {
                Cache::tags([$this->cacheTag])->flush();
                $this->stats['flushed']++;
                return true;
            } catch (\Exception $e) {
                Log::error("모든 쿼리 캐시 삭제 오류: {$e->getMessage()}", [
                    'exception' => $e
                ]);
                
                return false;
            }
        }
    }
    
    /**
     * 테이블과 관련된 모든 캐시를 삭제합니다.
     * 
     * @param string|array $tables 테이블 이름 또는 테이블 이름 배열
     * @return bool 삭제 성공 여부
     */
    public function flushByTables($tables)
    {
        if (!$this->enabled) {
            return false;
        }
        
        $tables = is_array($tables) ? $tables : [$tables];
        
        if ($this->cacheService) {
            $result = $this->cacheService->flushByTags(array_merge([$this->cacheTag], $tables));
            
            if ($result) {
                $this->stats['flushed'] += $result;
            }
            
            return $result > 0;
        } else {
            try {
                Cache::tags(array_merge([$this->cacheTag], $tables))->flush();
                $this->stats['flushed']++;
                return true;
            } catch (\Exception $e) {
                Log::error("테이블 관련 캐시 삭제 오류: {$e->getMessage()}", [
                    'tables' => $tables,
                    'exception' => $e
                ]);
                
                return false;
            }
        }
    }
    
    /**
     * 캐시 통계를 가져옵니다.
     * 
     * @return array 캐시 통계
     */
    public function getStats()
    {
        $total = $this->stats['hits'] + $this->stats['misses'];
        $hitRate = $total > 0 ? round(($this->stats['hits'] / $total) * 100, 2) : 0;
        
        return [
            'hits' => $this->stats['hits'],
            'misses' => $this->stats['misses'],
            'stored' => $this->stats['stored'],
            'flushed' => $this->stats['flushed'],
            'hit_rate' => $hitRate,
            'total_requests' => $total
        ];
    }
    
    /**
     * 캐시 키를 생성합니다.
     * 
     * @param string|QueryBuilder|EloquentBuilder $query 쿼리 또는 쿼리 빌더
     * @param array $bindings 쿼리 바인딩
     * @return string 캐시 키
     */
    protected function generateCacheKey($query, array $bindings = [])
    {
        if ($query instanceof QueryBuilder || $query instanceof EloquentBuilder) {
            $sql = $query->toSql();
            $bindings = $query->getBindings();
        } else {
            $sql = $query;
        }
        
        // 바인딩 값을 문자열로 변환
        $bindingsStr = '';
        
        foreach ($bindings as $binding) {
            if (is_null($binding)) {
                $bindingsStr .= 'NULL,';
            } elseif (is_bool($binding)) {
                $bindingsStr .= ($binding ? 'true' : 'false') . ',';
            } elseif (is_array($binding)) {
                $bindingsStr .= json_encode($binding) . ',';
            } else {
                $bindingsStr .= $binding . ',';
            }
        }
        
        // 해시 생성
        $hash = md5($sql . $bindingsStr);
        
        return $this->cachePrefix . $hash;
    }
    
    /**
     * 쿼리를 실행합니다.
     * 
     * @param string|QueryBuilder|EloquentBuilder $query 쿼리 또는 쿼리 빌더
     * @param array $bindings 쿼리 바인딩
     * @return mixed 쿼리 결과
     */
    protected function executeQuery($query, array $bindings = [])
    {
        try {
            if ($query instanceof QueryBuilder) {
                return $query->get();
            } elseif ($query instanceof EloquentBuilder) {
                return $query->get();
            } else {
                return DB::select($query, $bindings);
            }
        } catch (\Exception $e) {
            Log::error("쿼리 실행 오류: {$e->getMessage()}", [
                'query' => $query instanceof QueryBuilder || $query instanceof EloquentBuilder ? $query->toSql() : $query,
                'bindings' => $bindings,
                'exception' => $e
            ]);
            
            throw $e;
        }
    }
    
    /**
     * 쿼리 빌더에 캐싱 기능을 추가합니다.
     * 
     * @param QueryBuilder|EloquentBuilder $query 쿼리 빌더
     * @param int|null $ttl 캐시 유효 시간 (초)
     * @param array $tags 추가 캐시 태그
     * @return mixed 쿼리 결과
     */
    public function cacheQuery($query, ?int $ttl = null, array $tags = [])
    {
        if (!$this->enabled) {
            return $query instanceof EloquentBuilder ? $query->get() : $query->get();
        }
        
        // 테이블 이름을 태그로 추가
        if ($query instanceof QueryBuilder) {
            $tableName = $query->from;
            if (is_string($tableName)) {
                $tags[] = $tableName;
            }
        } elseif ($query instanceof EloquentBuilder) {
            $tableName = $query->getModel()->getTable();
            $tags[] = $tableName;
        }
        
        return $this->remember($query, [], $ttl, $tags);
    }
    
    /**
     * 모델 쿼리에 캐싱 기능을 추가하는 매크로를 등록합니다.
     * 
     * @return void
     */
    public function registerEloquentMacros()
    {
        $self = $this;
        
        // 쿼리 빌더에 캐시 매크로 추가
        if (!QueryBuilder::hasMacro('cache')) {
            QueryBuilder::macro('cache', function ($ttl = null, $tags = []) use ($self) {
                return $self->cacheQuery($this, $ttl, $tags);
            });
        }
        
        // Eloquent 빌더에 캐시 매크로 추가
        if (!EloquentBuilder::hasMacro('cache')) {
            EloquentBuilder::macro('cache', function ($ttl = null, $tags = []) use ($self) {
                return $self->cacheQuery($this, $ttl, $tags);
            });
        }
        
        // 캐시 무효화 매크로 추가
        if (!EloquentBuilder::hasMacro('flushCache')) {
            EloquentBuilder::macro('flushCache', function () use ($self) {
                $tableName = $this->getModel()->getTable();
                return $self->flushByTables($tableName);
            });
        }
    }
} 