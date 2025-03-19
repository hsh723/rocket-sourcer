<?php

namespace App\Services\Database;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Filesystem\Filesystem;

/**
 * 데이터베이스 마이그레이션 서비스
 * 
 * 데이터베이스 마이그레이션을 관리하고 실행하는 서비스입니다.
 * 마이그레이션 상태 확인, 마이그레이션 실행, 롤백 등의 기능을 제공합니다.
 */
class MigrationService
{
    /**
     * 마이그레이션 저장소
     * 
     * @var MigrationRepositoryInterface
     */
    protected $repository;
    
    /**
     * 마이그레이터
     * 
     * @var Migrator
     */
    protected $migrator;
    
    /**
     * 파일 시스템
     * 
     * @var Filesystem
     */
    protected $files;
    
    /**
     * 마이그레이션 경로
     * 
     * @var array
     */
    protected $paths;
    
    /**
     * 생성자
     * 
     * @param MigrationRepositoryInterface $repository
     * @param Migrator $migrator
     * @param Filesystem $files
     */
    public function __construct(
        MigrationRepositoryInterface $repository,
        Migrator $migrator,
        Filesystem $files
    ) {
        $this->repository = $repository;
        $this->migrator = $migrator;
        $this->files = $files;
        $this->paths = [database_path('migrations')];
    }
    
    /**
     * 마이그레이션 경로를 설정합니다.
     * 
     * @param array $paths
     * @return $this
     */
    public function setPaths(array $paths)
    {
        $this->paths = $paths;
        $this->migrator->setOutput(new \Symfony\Component\Console\Output\NullOutput());
        $this->migrator->setPaths($paths);
        
        return $this;
    }
    
    /**
     * 마이그레이션 저장소가 존재하는지 확인합니다.
     * 
     * @return bool
     */
    public function repositoryExists()
    {
        return $this->repository->repositoryExists();
    }
    
    /**
     * 마이그레이션 저장소를 생성합니다.
     * 
     * @return void
     */
    public function createRepository()
    {
        try {
            $this->repository->createRepository();
            Log::info("마이그레이션 저장소 생성됨");
        } catch (\Exception $e) {
            Log::error("마이그레이션 저장소 생성 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            throw $e;
        }
    }
    
    /**
     * 마이그레이션을 실행합니다.
     * 
     * @param int|null $step 실행할 마이그레이션 수 (null이면 모두 실행)
     * @return array 실행 결과
     */
    public function runMigrations($step = null)
    {
        try {
            // 마이그레이션 저장소가 없으면 생성
            if (!$this->repositoryExists()) {
                $this->createRepository();
            }
            
            // 마이그레이션 실행
            $this->migrator->setOutput(new \Symfony\Component\Console\Output\NullOutput());
            $this->migrator->setPaths($this->paths);
            
            $migrations = $this->migrator->run($this->paths, [
                'step' => $step
            ]);
            
            $migratedFiles = [];
            foreach ($migrations as $migration) {
                $migratedFiles[] = $migration;
            }
            
            Log::info("마이그레이션 실행 완료", [
                'count' => count($migratedFiles),
                'files' => $migratedFiles
            ]);
            
            return [
                'success' => true,
                'migrated' => $migratedFiles,
                'count' => count($migratedFiles)
            ];
        } catch (\Exception $e) {
            Log::error("마이그레이션 실행 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'migrated' => [],
                'count' => 0
            ];
        }
    }
    
    /**
     * 마이그레이션을 롤백합니다.
     * 
     * @param int|null $step 롤백할 배치 수 (null이면 마지막 배치만 롤백)
     * @return array 롤백 결과
     */
    public function rollbackMigrations($step = null)
    {
        try {
            $this->migrator->setOutput(new \Symfony\Component\Console\Output\NullOutput());
            $this->migrator->setPaths($this->paths);
            
            $migrations = $this->migrator->rollback($this->paths, [
                'step' => $step
            ]);
            
            $rolledBackFiles = [];
            foreach ($migrations as $migration) {
                $rolledBackFiles[] = $migration;
            }
            
            Log::info("마이그레이션 롤백 완료", [
                'count' => count($rolledBackFiles),
                'files' => $rolledBackFiles
            ]);
            
            return [
                'success' => true,
                'rolled_back' => $rolledBackFiles,
                'count' => count($rolledBackFiles)
            ];
        } catch (\Exception $e) {
            Log::error("마이그레이션 롤백 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'rolled_back' => [],
                'count' => 0
            ];
        }
    }
    
    /**
     * 모든 마이그레이션을 롤백합니다.
     * 
     * @return array 롤백 결과
     */
    public function resetMigrations()
    {
        try {
            $this->migrator->setOutput(new \Symfony\Component\Console\Output\NullOutput());
            $this->migrator->setPaths($this->paths);
            
            $migrations = $this->migrator->reset($this->paths);
            
            $resetFiles = [];
            foreach ($migrations as $migration) {
                $resetFiles[] = $migration;
            }
            
            Log::info("마이그레이션 리셋 완료", [
                'count' => count($resetFiles),
                'files' => $resetFiles
            ]);
            
            return [
                'success' => true,
                'reset' => $resetFiles,
                'count' => count($resetFiles)
            ];
        } catch (\Exception $e) {
            Log::error("마이그레이션 리셋 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'reset' => [],
                'count' => 0
            ];
        }
    }
    
    /**
     * 마이그레이션 상태를 확인합니다.
     * 
     * @return array 마이그레이션 상태
     */
    public function getMigrationStatus()
    {
        try {
            if (!$this->repositoryExists()) {
                return [
                    'success' => false,
                    'error' => '마이그레이션 저장소가 존재하지 않습니다.',
                    'migrations' => []
                ];
            }
            
            $this->migrator->setOutput(new \Symfony\Component\Console\Output\NullOutput());
            $this->migrator->setPaths($this->paths);
            
            $ran = $this->repository->getRan();
            $migrations = [];
            
            foreach ($this->migrator->getMigrationFiles($this->paths) as $file) {
                $migrations[] = [
                    'migration' => $this->migrator->getMigrationName($file),
                    'batch' => in_array($this->migrator->getMigrationName($file), $ran)
                        ? $this->repository->getMigrationBatch($this->migrator->getMigrationName($file))
                        : null,
                    'status' => in_array($this->migrator->getMigrationName($file), $ran)
                        ? 'Ran'
                        : 'Pending'
                ];
            }
            
            // 배치 번호로 정렬
            usort($migrations, function ($a, $b) {
                if ($a['batch'] === null && $b['batch'] === null) {
                    return strcmp($a['migration'], $b['migration']);
                }
                
                if ($a['batch'] === null) {
                    return 1;
                }
                
                if ($b['batch'] === null) {
                    return -1;
                }
                
                if ($a['batch'] === $b['batch']) {
                    return strcmp($a['migration'], $b['migration']);
                }
                
                return $a['batch'] - $b['batch'];
            });
            
            return [
                'success' => true,
                'migrations' => $migrations,
                'pending_count' => count(array_filter($migrations, function ($migration) {
                    return $migration['status'] === 'Pending';
                })),
                'ran_count' => count(array_filter($migrations, function ($migration) {
                    return $migration['status'] === 'Ran';
                })),
                'latest_batch' => $this->repository->getLastBatchNumber()
            ];
        } catch (\Exception $e) {
            Log::error("마이그레이션 상태 확인 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'migrations' => []
            ];
        }
    }
    
    /**
     * 마이그레이션 파일을 생성합니다.
     * 
     * @param string $name 마이그레이션 이름
     * @param string|null $table 테이블 이름
     * @param bool $create 테이블 생성 여부
     * @return array 생성 결과
     */
    public function createMigration($name, $table = null, $create = false)
    {
        try {
            $arguments = ['name' => $name];
            
            if ($table) {
                $arguments['--table'] = $table;
            }
            
            if ($create) {
                $arguments['--create'] = $table;
            }
            
            $exitCode = Artisan::call('make:migration', $arguments);
            
            if ($exitCode !== 0) {
                throw new \Exception("마이그레이션 파일 생성 실패: 종료 코드 {$exitCode}");
            }
            
            $output = Artisan::output();
            
            // 출력에서 파일 경로 추출
            preg_match('/Created Migration: (.+)/', $output, $matches);
            $migrationFile = isset($matches[1]) ? $matches[1] : null;
            
            Log::info("마이그레이션 파일 생성됨", [
                'name' => $name,
                'table' => $table,
                'create' => $create,
                'file' => $migrationFile
            ]);
            
            return [
                'success' => true,
                'file' => $migrationFile,
                'output' => trim($output)
            ];
        } catch (\Exception $e) {
            Log::error("마이그레이션 파일 생성 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'output' => Artisan::output()
            ];
        }
    }
    
    /**
     * 마이그레이션 파일을 삭제합니다.
     * 
     * @param string $name 마이그레이션 이름
     * @return array 삭제 결과
     */
    public function deleteMigration($name)
    {
        try {
            $files = $this->migrator->getMigrationFiles($this->paths);
            $found = false;
            $filePath = null;
            
            foreach ($files as $file) {
                $migrationName = $this->migrator->getMigrationName($file);
                
                if ($migrationName === $name) {
                    $filePath = $file;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                return [
                    'success' => false,
                    'error' => "마이그레이션 파일을 찾을 수 없습니다: {$name}"
                ];
            }
            
            // 마이그레이션이 이미 실행되었는지 확인
            $ran = $this->repository->getRan();
            
            if (in_array($name, $ran)) {
                return [
                    'success' => false,
                    'error' => "이미 실행된 마이그레이션은 삭제할 수 없습니다: {$name}"
                ];
            }
            
            // 파일 삭제
            $this->files->delete($filePath);
            
            Log::info("마이그레이션 파일 삭제됨", [
                'name' => $name,
                'file' => $filePath
            ]);
            
            return [
                'success' => true,
                'file' => $filePath
            ];
        } catch (\Exception $e) {
            Log::error("마이그레이션 파일 삭제 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 마이그레이션 저장소를 새로고침합니다.
     * 
     * @return array 새로고침 결과
     */
    public function refreshMigrations()
    {
        try {
            $resetResult = $this->resetMigrations();
            
            if (!$resetResult['success']) {
                return $resetResult;
            }
            
            $runResult = $this->runMigrations();
            
            Log::info("마이그레이션 새로고침 완료", [
                'reset_count' => $resetResult['count'],
                'run_count' => $runResult['count']
            ]);
            
            return [
                'success' => $runResult['success'],
                'reset' => $resetResult['reset'],
                'migrated' => $runResult['migrated'],
                'reset_count' => $resetResult['count'],
                'migrated_count' => $runResult['count']
            ];
        } catch (\Exception $e) {
            Log::error("마이그레이션 새로고침 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 데이터베이스 스키마를 덤프합니다.
     * 
     * @return array 덤프 결과
     */
    public function dumpSchema()
    {
        try {
            $exitCode = Artisan::call('schema:dump');
            
            if ($exitCode !== 0) {
                throw new \Exception("스키마 덤프 실패: 종료 코드 {$exitCode}");
            }
            
            $output = Artisan::output();
            
            Log::info("데이터베이스 스키마 덤프 완료");
            
            return [
                'success' => true,
                'output' => trim($output)
            ];
        } catch (\Exception $e) {
            Log::error("스키마 덤프 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'output' => Artisan::output()
            ];
        }
    }
    
    /**
     * 테이블 목록을 가져옵니다.
     * 
     * @return array 테이블 목록
     */
    public function getTables()
    {
        try {
            $tables = [];
            $connection = DB::connection();
            $databaseName = $connection->getDatabaseName();
            
            // 테이블 목록 가져오기
            $tableNames = $connection->getDoctrineSchemaManager()->listTableNames();
            
            foreach ($tableNames as $tableName) {
                // 마이그레이션 테이블 제외
                if ($tableName === 'migrations') {
                    continue;
                }
                
                // 테이블 정보 가져오기
                $columns = Schema::getColumnListing($tableName);
                $columnCount = count($columns);
                
                // 레코드 수 가져오기
                $recordCount = DB::table($tableName)->count();
                
                // 테이블 크기 가져오기 (MySQL 전용)
                $tableSize = 0;
                $tableSizeQuery = DB::select("
                    SELECT 
                        data_length + index_length as size
                    FROM 
                        information_schema.TABLES
                    WHERE 
                        table_schema = ? AND
                        table_name = ?
                ", [$databaseName, $tableName]);
                
                if (!empty($tableSizeQuery)) {
                    $tableSize = $tableSizeQuery[0]->size;
                }
                
                $tables[] = [
                    'name' => $tableName,
                    'columns' => $columns,
                    'column_count' => $columnCount,
                    'record_count' => $recordCount,
                    'size_bytes' => $tableSize,
                    'size_formatted' => $this->formatBytes($tableSize)
                ];
            }
            
            return [
                'success' => true,
                'tables' => $tables,
                'count' => count($tables)
            ];
        } catch (\Exception $e) {
            Log::error("테이블 목록 가져오기 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'tables' => [],
                'count' => 0
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
    protected function formatBytes($bytes, $precision = 2)
    {
        if ($bytes <= 0) {
            return '0 B';
        }
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $base = log($bytes, 1024);
        $power = min(floor($base), count($units) - 1);
        
        return round(pow(1024, $base - $power), $precision) . ' ' . $units[$power];
    }
} 