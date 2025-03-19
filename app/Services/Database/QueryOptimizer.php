<?php

namespace App\Services\Database;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * 쿼리 최적화 서비스
 * 
 * 데이터베이스 쿼리 성능을 최적화하기 위한 유틸리티 클래스입니다.
 * 쿼리 힌트, 선택적 로딩, 인덱스 활용 등의 기능을 제공합니다.
 */
class QueryOptimizer
{
    /**
     * 쿼리 로깅 활성화 여부
     * 
     * @var bool
     */
    protected $loggingEnabled = false;
    
    /**
     * 쿼리 로그 저장소
     * 
     * @var array
     */
    protected $queryLog = [];
    
    /**
     * 쿼리 최적화 설정
     * 
     * @var array
     */
    protected $optimizationSettings = [
        'chunk_size' => 1000,
        'max_eager_relations' => 3,
        'use_query_hints' => true,
        'use_index_hints' => true,
        'use_query_caching' => true
    ];
    
    /**
     * 생성자
     * 
     * @param array $settings 최적화 설정
     */
    public function __construct(array $settings = [])
    {
        $this->optimizationSettings = array_merge($this->optimizationSettings, $settings);
    }
    
    /**
     * 쿼리 로깅을 활성화합니다.
     * 
     * @param bool $enabled 활성화 여부
     * @return $this
     */
    public function enableLogging(bool $enabled = true)
    {
        $this->loggingEnabled = $enabled;
        
        if ($enabled) {
            DB::enableQueryLog();
        } else {
            DB::disableQueryLog();
        }
        
        return $this;
    }
    
    /**
     * 쿼리 로그를 가져옵니다.
     * 
     * @return array 쿼리 로그
     */
    public function getQueryLog()
    {
        if ($this->loggingEnabled) {
            $this->queryLog = array_merge($this->queryLog, DB::getQueryLog());
            DB::flushQueryLog();
        }
        
        return $this->queryLog;
    }
    
    /**
     * 쿼리 로그를 분석하여 최적화 제안을 제공합니다.
     * 
     * @return array 최적화 제안
     */
    public function analyzeQueries()
    {
        $log = $this->getQueryLog();
        $suggestions = [];
        
        if (empty($log)) {
            return ['message' => '분석할 쿼리 로그가 없습니다.'];
        }
        
        $slowQueries = [];
        $duplicateQueries = [];
        $queriesWithoutIndex = [];
        
        // 쿼리 분석
        $queryHashes = [];
        foreach ($log as $index => $query) {
            $sql = $query['query'];
            $time = $query['time'] ?? 0;
            
            // 느린 쿼리 감지 (100ms 이상)
            if ($time > 100) {
                $slowQueries[] = [
                    'sql' => $sql,
                    'time' => $time,
                    'index' => $index
                ];
            }
            
            // 중복 쿼리 감지
            $hash = md5($sql . json_encode($query['bindings']));
            if (isset($queryHashes[$hash])) {
                $duplicateQueries[] = [
                    'sql' => $sql,
                    'count' => ++$queryHashes[$hash]['count'],
                    'indexes' => array_merge($queryHashes[$hash]['indexes'], [$index])
                ];
            } else {
                $queryHashes[$hash] = [
                    'count' => 1,
                    'indexes' => [$index]
                ];
            }
            
            // 인덱스 없는 쿼리 감지 (WHERE 절이 있지만 인덱스를 사용하지 않는 경우)
            if (stripos($sql, 'where') !== false && stripos($sql, 'index') === false) {
                // 간단한 휴리스틱 검사 (실제로는 EXPLAIN을 사용해야 함)
                $queriesWithoutIndex[] = [
                    'sql' => $sql,
                    'index' => $index
                ];
            }
        }
        
        // 제안 생성
        if (!empty($slowQueries)) {
            $suggestions['slow_queries'] = [
                'message' => count($slowQueries) . '개의 느린 쿼리가 감지되었습니다.',
                'queries' => $slowQueries,
                'suggestions' => [
                    '해당 쿼리에 인덱스 추가를 고려하세요.',
                    '불필요한 조인이나 서브쿼리가 있는지 확인하세요.',
                    '필요한 컬럼만 선택하도록 쿼리를 수정하세요.'
                ]
            ];
        }
        
        if (!empty($duplicateQueries)) {
            $suggestions['duplicate_queries'] = [
                'message' => count($duplicateQueries) . '개의 중복 쿼리가 감지되었습니다.',
                'queries' => $duplicateQueries,
                'suggestions' => [
                    '중복 쿼리를 캐싱하거나 결과를 재사용하세요.',
                    'N+1 문제가 있는지 확인하고 Eager Loading을 사용하세요.',
                    '쿼리 빌더 체인에서 중복 호출이 있는지 확인하세요.'
                ]
            ];
        }
        
        if (!empty($queriesWithoutIndex)) {
            $suggestions['queries_without_index'] = [
                'message' => count($queriesWithoutIndex) . '개의 쿼리가 인덱스를 사용하지 않는 것으로 보입니다.',
                'queries' => $queriesWithoutIndex,
                'suggestions' => [
                    'WHERE 절에 사용된 컬럼에 인덱스를 추가하세요.',
                    '복합 인덱스 사용을 고려하세요.',
                    'EXPLAIN 명령을 사용하여 쿼리 실행 계획을 확인하세요.'
                ]
            ];
        }
        
        return $suggestions;
    }
    
    /**
     * 쿼리 빌더에 최적화를 적용합니다.
     * 
     * @param Builder $query 쿼리 빌더
     * @param array $options 최적화 옵션
     * @return Builder 최적화된 쿼리 빌더
     */
    public function optimizeQuery(Builder $query, array $options = [])
    {
        $options = array_merge($this->optimizationSettings, $options);
        
        // 필요한 컬럼만 선택
        if (isset($options['select']) && is_array($options['select'])) {
            $query->select($options['select']);
        }
        
        // 인덱스 힌트 적용
        if ($options['use_index_hints'] && isset($options['index_hint'])) {
            $this->applyIndexHint($query, $options['index_hint']);
        }
        
        // 쿼리 힌트 적용
        if ($options['use_query_hints'] && isset($options['query_hint'])) {
            $this->applyQueryHint($query, $options['query_hint']);
        }
        
        // Eager Loading 최적화
        if (isset($options['with']) && is_array($options['with'])) {
            // 너무 많은 관계를 한 번에 로드하지 않도록 제한
            $relations = array_slice($options['with'], 0, $options['max_eager_relations']);
            $query->with($relations);
        }
        
        // 대용량 데이터 처리를 위한 청크 크기 설정
        if (isset($options['chunk_callback']) && is_callable($options['chunk_callback'])) {
            // 이 메서드는 쿼리를 즉시 실행하므로 반환값이 없음
            $query->chunk($options['chunk_size'], $options['chunk_callback']);
            return null;
        }
        
        return $query;
    }
    
    /**
     * 모델 쿼리에 최적화를 적용합니다.
     * 
     * @param string|Model $model 모델 클래스 또는 인스턴스
     * @param array $options 최적화 옵션
     * @return Builder 최적화된 쿼리 빌더
     */
    public function optimizeModel($model, array $options = [])
    {
        $query = is_string($model) ? $model::query() : $model->newQuery();
        return $this->optimizeQuery($query, $options);
    }
    
    /**
     * 인덱스 힌트를 적용합니다.
     * 
     * @param Builder $query 쿼리 빌더
     * @param string|array $indexHint 인덱스 힌트
     * @return Builder 쿼리 빌더
     */
    protected function applyIndexHint(Builder $query, $indexHint)
    {
        // MySQL 인덱스 힌트 적용
        // 예: USE INDEX (idx_created_at)
        if (is_string($indexHint)) {
            $sql = $query->toSql();
            $tableName = $query->getModel()->getTable();
            
            // FROM 절 뒤에 인덱스 힌트 추가
            $newSql = preg_replace(
                "/FROM `{$tableName}`/i",
                "FROM `{$tableName}` USE INDEX ({$indexHint})",
                $sql
            );
            
            // 원시 쿼리로 변환
            $bindings = $query->getBindings();
            $query = DB::raw($newSql);
            
            // 바인딩 복원 (실제 구현에서는 더 복잡할 수 있음)
            foreach ($bindings as $binding) {
                $query = DB::raw(preg_replace('/\?/', DB::getPdo()->quote($binding), $query, 1));
            }
        }
        
        return $query;
    }
    
    /**
     * 쿼리 힌트를 적용합니다.
     * 
     * @param Builder $query 쿼리 빌더
     * @param string|array $queryHint 쿼리 힌트
     * @return Builder 쿼리 빌더
     */
    protected function applyQueryHint(Builder $query, $queryHint)
    {
        // MySQL 쿼리 힌트 적용
        // 예: SELECT SQL_CALC_FOUND_ROWS *
        if (is_string($queryHint)) {
            $sql = $query->toSql();
            
            // SELECT 뒤에 쿼리 힌트 추가
            $newSql = preg_replace(
                "/SELECT/i",
                "SELECT {$queryHint}",
                $sql
            );
            
            // 원시 쿼리로 변환
            $bindings = $query->getBindings();
            $query = DB::raw($newSql);
            
            // 바인딩 복원 (실제 구현에서는 더 복잡할 수 있음)
            foreach ($bindings as $binding) {
                $query = DB::raw(preg_replace('/\?/', DB::getPdo()->quote($binding), $query, 1));
            }
        }
        
        return $query;
    }
    
    /**
     * 테이블에 필요한 인덱스를 제안합니다.
     * 
     * @param string $table 테이블 이름
     * @param array $queries 분석할 쿼리 배열
     * @return array 인덱스 제안
     */
    public function suggestIndexes(string $table, array $queries = [])
    {
        $suggestions = [];
        $columns = Schema::getColumnListing($table);
        $existingIndexes = $this->getTableIndexes($table);
        
        // 쿼리가 제공되지 않은 경우 쿼리 로그 사용
        if (empty($queries)) {
            $log = $this->getQueryLog();
            foreach ($log as $query) {
                if (stripos($query['query'], $table) !== false) {
                    $queries[] = $query['query'];
                }
            }
        }
        
        // 쿼리에서 WHERE 절 분석
        $whereColumns = [];
        foreach ($queries as $sql) {
            if (preg_match_all("/WHERE\s+`?(\w+)`?\s*=/i", $sql, $matches)) {
                foreach ($matches[1] as $column) {
                    if (in_array($column, $columns)) {
                        $whereColumns[$column] = ($whereColumns[$column] ?? 0) + 1;
                    }
                }
            }
        }
        
        // 인덱스 제안
        foreach ($whereColumns as $column => $count) {
            $alreadyIndexed = false;
            foreach ($existingIndexes as $index) {
                if ($index['column_name'] === $column) {
                    $alreadyIndexed = true;
                    break;
                }
            }
            
            if (!$alreadyIndexed && $count >= 3) { // 3번 이상 사용된 컬럼만 제안
                $suggestions[] = [
                    'column' => $column,
                    'usage_count' => $count,
                    'suggestion' => "CREATE INDEX idx_{$table}_{$column} ON {$table} ({$column});"
                ];
            }
        }
        
        // 복합 인덱스 제안 (간단한 휴리스틱)
        $joinColumns = [];
        foreach ($queries as $sql) {
            if (preg_match_all("/JOIN\s+`?(\w+)`?\s+ON\s+`?(\w+)`?\.`?(\w+)`?\s*=\s*`?(\w+)`?\.`?(\w+)`?/i", $sql, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $joinTable = $match[1];
                    $leftTable = $match[2];
                    $leftColumn = $match[3];
                    $rightTable = $match[4];
                    $rightColumn = $match[5];
                    
                    if ($leftTable === $table && in_array($leftColumn, $columns)) {
                        $joinColumns[$leftColumn] = ($joinColumns[$leftColumn] ?? 0) + 1;
                    } elseif ($rightTable === $table && in_array($rightColumn, $columns)) {
                        $joinColumns[$rightColumn] = ($joinColumns[$rightColumn] ?? 0) + 1;
                    }
                }
            }
        }
        
        foreach ($joinColumns as $column => $count) {
            $alreadyIndexed = false;
            foreach ($existingIndexes as $index) {
                if ($index['column_name'] === $column) {
                    $alreadyIndexed = true;
                    break;
                }
            }
            
            if (!$alreadyIndexed && $count >= 2) { // 2번 이상 조인에 사용된 컬럼만 제안
                $suggestions[] = [
                    'column' => $column,
                    'usage_count' => $count,
                    'suggestion' => "CREATE INDEX idx_{$table}_{$column} ON {$table} ({$column});",
                    'type' => 'join'
                ];
            }
        }
        
        return $suggestions;
    }
    
    /**
     * 테이블의 기존 인덱스를 가져옵니다.
     * 
     * @param string $table 테이블 이름
     * @return array 인덱스 정보
     */
    protected function getTableIndexes(string $table)
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM {$table}");
            return $indexes;
        } catch (\Exception $e) {
            Log::error("인덱스 정보 조회 오류: {$e->getMessage()}", [
                'table' => $table,
                'exception' => $e
            ]);
            
            return [];
        }
    }
} 