<?php

namespace App\Services\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * 데이터베이스 인덱스 관리자
 * 
 * 데이터베이스 인덱스를 관리하고 최적화하는 서비스입니다.
 * 인덱스 생성, 삭제, 분석, 최적화 등의 기능을 제공합니다.
 */
class IndexManager
{
    /**
     * 쿼리 최적화 서비스
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
     * 테이블의 인덱스 목록을 가져옵니다.
     * 
     * @param string $table 테이블 이름
     * @return array 인덱스 목록
     */
    public function getTableIndexes(string $table)
    {
        try {
            // 테이블 존재 확인
            if (!Schema::hasTable($table)) {
                throw new \Exception("테이블이 존재하지 않습니다: {$table}");
            }
            
            // 인덱스 정보 조회
            $indexes = DB::select("SHOW INDEX FROM {$table}");
            $indexInfo = [];
            
            foreach ($indexes as $index) {
                $indexName = $index->Key_name;
                
                if (!isset($indexInfo[$indexName])) {
                    $indexInfo[$indexName] = [
                        'name' => $indexName,
                        'columns' => [],
                        'unique' => $index->Non_unique == 0,
                        'type' => $index->Index_type,
                        'cardinality' => $index->Cardinality,
                        'visible' => $index->Visible ?? 'YES',
                        'is_primary' => $indexName === 'PRIMARY'
                    ];
                }
                
                $indexInfo[$indexName]['columns'][] = [
                    'name' => $index->Column_name,
                    'sub_part' => $index->Sub_part,
                    'nullable' => $index->Null === 'YES',
                    'seq_in_index' => $index->Seq_in_index
                ];
                
                // 컬럼 순서대로 정렬
                usort($indexInfo[$indexName]['columns'], function ($a, $b) {
                    return $a['seq_in_index'] - $b['seq_in_index'];
                });
            }
            
            Log::info("테이블 인덱스 조회 완료", [
                'table' => $table,
                'indexes_count' => count($indexInfo)
            ]);
            
            return [
                'success' => true,
                'table' => $table,
                'indexes' => array_values($indexInfo),
                'count' => count($indexInfo)
            ];
        } catch (\Exception $e) {
            Log::error("테이블 인덱스 조회 오류: {$e->getMessage()}", [
                'table' => $table,
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'table' => $table,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 인덱스를 생성합니다.
     * 
     * @param string $table 테이블 이름
     * @param array $columns 컬럼 목록
     * @param string|null $name 인덱스 이름 (null이면 자동 생성)
     * @param bool $unique 고유 인덱스 여부
     * @param string $type 인덱스 유형 (BTREE, HASH, FULLTEXT, SPATIAL)
     * @return array 생성 결과
     */
    public function createIndex(
        string $table,
        array $columns,
        ?string $name = null,
        bool $unique = false,
        string $type = 'BTREE'
    ) {
        try {
            // 테이블 존재 확인
            if (!Schema::hasTable($table)) {
                throw new \Exception("테이블이 존재하지 않습니다: {$table}");
            }
            
            // 컬럼 존재 확인
            $tableColumns = Schema::getColumnListing($table);
            
            foreach ($columns as $column) {
                // 컬럼 이름과 길이 분리 (예: name(20))
                $columnName = $column;
                $length = null;
                
                if (preg_match('/^([^\(]+)\((\d+)\)$/', $column, $matches)) {
                    $columnName = $matches[1];
                    $length = $matches[2];
                }
                
                if (!in_array($columnName, $tableColumns)) {
                    throw new \Exception("컬럼이 존재하지 않습니다: {$columnName}");
                }
            }
            
            // 인덱스 이름 생성
            if ($name === null) {
                $columnNames = array_map(function ($column) {
                    return preg_replace('/\([^)]+\)/', '', $column);
                }, $columns);
                
                $name = $table . '_' . implode('_', $columnNames) . '_idx';
                
                // 이름 길이 제한 (MySQL 최대 64자)
                if (strlen($name) > 64) {
                    $name = substr($name, 0, 60) . '_idx';
                }
            }
            
            // 인덱스 이미 존재하는지 확인
            $existingIndexes = $this->getTableIndexes($table);
            
            if ($existingIndexes['success']) {
                foreach ($existingIndexes['indexes'] as $index) {
                    if ($index['name'] === $name) {
                        throw new \Exception("인덱스가 이미 존재합니다: {$name}");
                    }
                }
            }
            
            // 인덱스 생성 쿼리 구성
            $indexType = strtoupper($type);
            $uniqueStr = $unique ? 'UNIQUE' : '';
            
            // 컬럼 문자열 구성
            $columnStr = implode(', ', array_map(function ($column) {
                return "`{$column}`";
            }, $columns));
            
            $query = "CREATE {$uniqueStr} INDEX `{$name}` USING {$indexType} ON `{$table}` ({$columnStr})";
            
            // 인덱스 생성
            DB::statement($query);
            
            Log::info("인덱스 생성 완료", [
                'table' => $table,
                'name' => $name,
                'columns' => $columns,
                'unique' => $unique,
                'type' => $type
            ]);
            
            return [
                'success' => true,
                'table' => $table,
                'name' => $name,
                'columns' => $columns,
                'unique' => $unique,
                'type' => $type
            ];
        } catch (\Exception $e) {
            Log::error("인덱스 생성 오류: {$e->getMessage()}", [
                'table' => $table,
                'columns' => $columns,
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'table' => $table,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 인덱스를 삭제합니다.
     * 
     * @param string $table 테이블 이름
     * @param string $name 인덱스 이름
     * @return array 삭제 결과
     */
    public function dropIndex(string $table, string $name)
    {
        try {
            // 테이블 존재 확인
            if (!Schema::hasTable($table)) {
                throw new \Exception("테이블이 존재하지 않습니다: {$table}");
            }
            
            // 인덱스 존재 확인
            $existingIndexes = $this->getTableIndexes($table);
            $indexExists = false;
            
            if ($existingIndexes['success']) {
                foreach ($existingIndexes['indexes'] as $index) {
                    if ($index['name'] === $name) {
                        $indexExists = true;
                        break;
                    }
                }
            }
            
            if (!$indexExists) {
                throw new \Exception("인덱스가 존재하지 않습니다: {$name}");
            }
            
            // 기본 키인 경우 다른 방식으로 삭제
            if ($name === 'PRIMARY') {
                DB::statement("ALTER TABLE `{$table}` DROP PRIMARY KEY");
            } else {
                DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$name}`");
            }
            
            Log::info("인덱스 삭제 완료", [
                'table' => $table,
                'name' => $name
            ]);
            
            return [
                'success' => true,
                'table' => $table,
                'name' => $name
            ];
        } catch (\Exception $e) {
            Log::error("인덱스 삭제 오류: {$e->getMessage()}", [
                'table' => $table,
                'name' => $name,
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'table' => $table,
                'name' => $name,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 인덱스 사용 통계를 가져옵니다.
     * 
     * @param string|null $table 테이블 이름 (null이면 모든 테이블)
     * @return array 인덱스 사용 통계
     */
    public function getIndexUsageStats(?string $table = null)
    {
        try {
            $query = "
                SELECT
                    t.TABLE_SCHEMA as `database`,
                    t.TABLE_NAME as `table`,
                    s.INDEX_NAME as `index`,
                    s.COLUMN_NAME as `column`,
                    s.SEQ_IN_INDEX as `seq`,
                    s.CARDINALITY as `cardinality`,
                    t.TABLE_ROWS as `rows`,
                    ROUND(((s.CARDINALITY / NULLIF(t.TABLE_ROWS, 0)) * 100), 2) as `selectivity`,
                    i.ROWS_READ as `rows_read`,
                    i.ROWS_SELECTED as `rows_selected`
                FROM
                    information_schema.STATISTICS s
                JOIN
                    information_schema.TABLES t ON s.TABLE_SCHEMA = t.TABLE_SCHEMA AND s.TABLE_NAME = t.TABLE_NAME
                LEFT JOIN
                    performance_schema.table_io_waits_summary_by_index_usage i ON i.OBJECT_SCHEMA = t.TABLE_SCHEMA
                    AND i.OBJECT_NAME = t.TABLE_NAME
                    AND i.INDEX_NAME = s.INDEX_NAME
                WHERE
                    t.TABLE_SCHEMA = DATABASE()
            ";
            
            if ($table !== null) {
                $query .= " AND t.TABLE_NAME = '{$table}'";
            }
            
            $query .= " ORDER BY t.TABLE_NAME, s.INDEX_NAME, s.SEQ_IN_INDEX";
            
            $results = DB::select($query);
            $stats = [];
            
            foreach ($results as $row) {
                $tableName = $row->table;
                $indexName = $row->index;
                
                if (!isset($stats[$tableName])) {
                    $stats[$tableName] = [
                        'table' => $tableName,
                        'rows' => $row->rows,
                        'indexes' => []
                    ];
                }
                
                if (!isset($stats[$tableName]['indexes'][$indexName])) {
                    $stats[$tableName]['indexes'][$indexName] = [
                        'name' => $indexName,
                        'columns' => [],
                        'cardinality' => $row->cardinality,
                        'selectivity' => $row->selectivity,
                        'rows_read' => $row->rows_read,
                        'rows_selected' => $row->rows_selected,
                        'usage_ratio' => $row->rows_read > 0 ? ($row->rows_selected / $row->rows_read) : 0
                    ];
                }
                
                $stats[$tableName]['indexes'][$indexName]['columns'][] = [
                    'name' => $row->column,
                    'seq' => $row->seq
                ];
            }
            
            // 배열 형태로 변환
            $result = [];
            
            foreach ($stats as $tableName => $tableStats) {
                $tableStats['indexes'] = array_values($tableStats['indexes']);
                $result[] = $tableStats;
            }
            
            Log::info("인덱스 사용 통계 조회 완료", [
                'table' => $table ?? 'all',
                'tables_count' => count($result)
            ]);
            
            return [
                'success' => true,
                'tables' => $result,
                'count' => count($result)
            ];
        } catch (\Exception $e) {
            Log::error("인덱스 사용 통계 조회 오류: {$e->getMessage()}", [
                'table' => $table ?? 'all',
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 인덱스 최적화 제안을 가져옵니다.
     * 
     * @param string|null $table 테이블 이름 (null이면 모든 테이블)
     * @return array 최적화 제안
     */
    public function getIndexSuggestions(?string $table = null)
    {
        try {
            if ($this->queryOptimizer === null) {
                throw new \Exception("쿼리 최적화 서비스가 설정되지 않았습니다.");
            }
            
            $suggestions = [];
            
            if ($table !== null) {
                // 특정 테이블에 대한 제안
                $tableSuggestions = $this->queryOptimizer->suggestIndexes($table);
                
                if (!empty($tableSuggestions)) {
                    $suggestions[$table] = $tableSuggestions;
                }
            } else {
                // 모든 테이블에 대한 제안
                $tables = Schema::getConnection()->getDoctrineSchemaManager()->listTableNames();
                
                foreach ($tables as $tableName) {
                    $tableSuggestions = $this->queryOptimizer->suggestIndexes($tableName);
                    
                    if (!empty($tableSuggestions)) {
                        $suggestions[$tableName] = $tableSuggestions;
                    }
                }
            }
            
            // 중복 인덱스 찾기
            $duplicateIndexes = $this->findDuplicateIndexes($table);
            
            // 사용되지 않는 인덱스 찾기
            $unusedIndexes = $this->findUnusedIndexes($table);
            
            Log::info("인덱스 최적화 제안 완료", [
                'table' => $table ?? 'all',
                'suggestions_count' => count($suggestions),
                'duplicate_indexes_count' => count($duplicateIndexes),
                'unused_indexes_count' => count($unusedIndexes)
            ]);
            
            return [
                'success' => true,
                'table' => $table ?? 'all',
                'suggestions' => $suggestions,
                'duplicate_indexes' => $duplicateIndexes,
                'unused_indexes' => $unusedIndexes
            ];
        } catch (\Exception $e) {
            Log::error("인덱스 최적화 제안 오류: {$e->getMessage()}", [
                'table' => $table ?? 'all',
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'table' => $table ?? 'all',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 중복 인덱스를 찾습니다.
     * 
     * @param string|null $table 테이블 이름 (null이면 모든 테이블)
     * @return array 중복 인덱스 목록
     */
    protected function findDuplicateIndexes(?string $table = null)
    {
        try {
            $tables = [];
            
            if ($table !== null) {
                $tables[] = $table;
            } else {
                $tables = Schema::getConnection()->getDoctrineSchemaManager()->listTableNames();
            }
            
            $duplicates = [];
            
            foreach ($tables as $tableName) {
                $indexes = $this->getTableIndexes($tableName);
                
                if (!$indexes['success']) {
                    continue;
                }
                
                $indexColumns = [];
                
                foreach ($indexes['indexes'] as $index) {
                    // 컬럼 이름만 추출
                    $columns = array_map(function ($column) {
                        return $column['name'];
                    }, $index['columns']);
                    
                    // 컬럼 이름을 정렬하여 문자열로 변환
                    sort($columns);
                    $columnKey = implode(',', $columns);
                    
                    if (!isset($indexColumns[$columnKey])) {
                        $indexColumns[$columnKey] = [];
                    }
                    
                    $indexColumns[$columnKey][] = [
                        'name' => $index['name'],
                        'columns' => $index['columns'],
                        'unique' => $index['unique'],
                        'type' => $index['type']
                    ];
                }
                
                // 중복 인덱스 찾기
                foreach ($indexColumns as $columnKey => $indexList) {
                    if (count($indexList) > 1) {
                        if (!isset($duplicates[$tableName])) {
                            $duplicates[$tableName] = [];
                        }
                        
                        $duplicates[$tableName][] = [
                            'columns' => $columnKey,
                            'indexes' => $indexList
                        ];
                    }
                }
            }
            
            return $duplicates;
        } catch (\Exception $e) {
            Log::error("중복 인덱스 찾기 오류: {$e->getMessage()}", [
                'table' => $table ?? 'all',
                'exception' => $e
            ]);
            
            return [];
        }
    }
    
    /**
     * 사용되지 않는 인덱스를 찾습니다.
     * 
     * @param string|null $table 테이블 이름 (null이면 모든 테이블)
     * @return array 사용되지 않는 인덱스 목록
     */
    protected function findUnusedIndexes(?string $table = null)
    {
        try {
            $query = "
                SELECT
                    object_schema as `database`,
                    object_name as `table`,
                    index_name as `index`,
                    count_star as `total_io`,
                    count_read as `reads`,
                    count_write as `writes`,
                    count_fetch as `fetches`
                FROM
                    performance_schema.table_io_waits_summary_by_index_usage
                WHERE
                    index_name IS NOT NULL
                    AND count_star = 0
                    AND object_schema = DATABASE()
            ";
            
            if ($table !== null) {
                $query .= " AND object_name = '{$table}'";
            }
            
            $results = DB::select($query);
            $unused = [];
            
            foreach ($results as $row) {
                $tableName = $row->table;
                
                if (!isset($unused[$tableName])) {
                    $unused[$tableName] = [];
                }
                
                // PRIMARY 키는 제외
                if ($row->index !== 'PRIMARY') {
                    $unused[$tableName][] = [
                        'name' => $row->index,
                        'total_io' => $row->total_io,
                        'reads' => $row->reads,
                        'writes' => $row->writes,
                        'fetches' => $row->fetches
                    ];
                }
            }
            
            return $unused;
        } catch (\Exception $e) {
            Log::error("사용되지 않는 인덱스 찾기 오류: {$e->getMessage()}", [
                'table' => $table ?? 'all',
                'exception' => $e
            ]);
            
            return [];
        }
    }
    
    /**
     * 인덱스를 재구축합니다.
     * 
     * @param string $table 테이블 이름
     * @param string|null $index 인덱스 이름 (null이면 모든 인덱스)
     * @return array 재구축 결과
     */
    public function rebuildIndex(string $table, ?string $index = null)
    {
        try {
            // 테이블 존재 확인
            if (!Schema::hasTable($table)) {
                throw new \Exception("테이블이 존재하지 않습니다: {$table}");
            }
            
            if ($index !== null) {
                // 특정 인덱스 재구축
                $query = "ALTER TABLE `{$table}` REBUILD INDEX `{$index}`";
                DB::statement($query);
                
                Log::info("인덱스 재구축 완료", [
                    'table' => $table,
                    'index' => $index
                ]);
                
                return [
                    'success' => true,
                    'table' => $table,
                    'index' => $index
                ];
            } else {
                // 테이블 최적화 (모든 인덱스 재구축)
                $query = "OPTIMIZE TABLE `{$table}`";
                $result = DB::select($query);
                
                Log::info("테이블 최적화 완료", [
                    'table' => $table,
                    'result' => $result
                ]);
                
                return [
                    'success' => true,
                    'table' => $table,
                    'result' => $result
                ];
            }
        } catch (\Exception $e) {
            Log::error("인덱스 재구축 오류: {$e->getMessage()}", [
                'table' => $table,
                'index' => $index ?? 'all',
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'table' => $table,
                'index' => $index ?? 'all',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 인덱스 분석을 실행합니다.
     * 
     * @param string $table 테이블 이름
     * @return array 분석 결과
     */
    public function analyzeTable(string $table)
    {
        try {
            // 테이블 존재 확인
            if (!Schema::hasTable($table)) {
                throw new \Exception("테이블이 존재하지 않습니다: {$table}");
            }
            
            $query = "ANALYZE TABLE `{$table}`";
            $result = DB::select($query);
            
            Log::info("테이블 분석 완료", [
                'table' => $table,
                'result' => $result
            ]);
            
            return [
                'success' => true,
                'table' => $table,
                'result' => $result
            ];
        } catch (\Exception $e) {
            Log::error("테이블 분석 오류: {$e->getMessage()}", [
                'table' => $table,
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'table' => $table,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 인덱스 최적화 계획을 실행합니다.
     * 
     * @param array $plan 최적화 계획
     * @return array 실행 결과
     */
    public function executeOptimizationPlan(array $plan)
    {
        try {
            $results = [
                'create' => [],
                'drop' => [],
                'rebuild' => [],
                'analyze' => []
            ];
            
            // 인덱스 생성
            if (isset($plan['create']) && is_array($plan['create'])) {
                foreach ($plan['create'] as $item) {
                    if (!isset($item['table']) || !isset($item['columns'])) {
                        continue;
                    }
                    
                    $result = $this->createIndex(
                        $item['table'],
                        $item['columns'],
                        $item['name'] ?? null,
                        $item['unique'] ?? false,
                        $item['type'] ?? 'BTREE'
                    );
                    
                    $results['create'][] = $result;
                }
            }
            
            // 인덱스 삭제
            if (isset($plan['drop']) && is_array($plan['drop'])) {
                foreach ($plan['drop'] as $item) {
                    if (!isset($item['table']) || !isset($item['name'])) {
                        continue;
                    }
                    
                    $result = $this->dropIndex($item['table'], $item['name']);
                    $results['drop'][] = $result;
                }
            }
            
            // 인덱스 재구축
            if (isset($plan['rebuild']) && is_array($plan['rebuild'])) {
                foreach ($plan['rebuild'] as $item) {
                    if (!isset($item['table'])) {
                        continue;
                    }
                    
                    $result = $this->rebuildIndex($item['table'], $item['index'] ?? null);
                    $results['rebuild'][] = $result;
                }
            }
            
            // 테이블 분석
            if (isset($plan['analyze']) && is_array($plan['analyze'])) {
                foreach ($plan['analyze'] as $item) {
                    if (!isset($item['table'])) {
                        continue;
                    }
                    
                    $result = $this->analyzeTable($item['table']);
                    $results['analyze'][] = $result;
                }
            }
            
            Log::info("인덱스 최적화 계획 실행 완료", [
                'create_count' => count($results['create']),
                'drop_count' => count($results['drop']),
                'rebuild_count' => count($results['rebuild']),
                'analyze_count' => count($results['analyze'])
            ]);
            
            return [
                'success' => true,
                'results' => $results
            ];
        } catch (\Exception $e) {
            Log::error("인덱스 최적화 계획 실행 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
} 