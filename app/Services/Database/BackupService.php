<?php

namespace App\Services\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * 데이터베이스 백업 서비스
 * 
 * 데이터베이스 백업 및 복원 기능을 제공하는 서비스입니다.
 * 자동 백업 스케줄링, 백업 파일 관리, 백업 복원 등의 기능을 제공합니다.
 */
class BackupService
{
    /**
     * 백업 저장 경로
     * 
     * @var string
     */
    protected $backupPath;
    
    /**
     * 백업 파일 접두사
     * 
     * @var string
     */
    protected $filePrefix;
    
    /**
     * 백업 파일 확장자
     * 
     * @var string
     */
    protected $fileExtension;
    
    /**
     * 최대 백업 파일 수
     * 
     * @var int
     */
    protected $maxBackupFiles;
    
    /**
     * 생성자
     * 
     * @param string $backupPath 백업 저장 경로
     * @param string $filePrefix 백업 파일 접두사
     * @param string $fileExtension 백업 파일 확장자
     * @param int $maxBackupFiles 최대 백업 파일 수
     */
    public function __construct(
        string $backupPath = 'backups/database',
        string $filePrefix = 'db_backup_',
        string $fileExtension = '.sql',
        int $maxBackupFiles = 10
    ) {
        $this->backupPath = $backupPath;
        $this->filePrefix = $filePrefix;
        $this->fileExtension = $fileExtension;
        $this->maxBackupFiles = $maxBackupFiles;
        
        // 백업 디렉토리 생성
        $this->ensureBackupDirectoryExists();
    }
    
    /**
     * 백업 디렉토리가 존재하는지 확인하고 없으면 생성합니다.
     * 
     * @return void
     */
    protected function ensureBackupDirectoryExists()
    {
        if (!Storage::exists($this->backupPath)) {
            Storage::makeDirectory($this->backupPath);
            Log::info("백업 디렉토리 생성됨: {$this->backupPath}");
        }
    }
    
    /**
     * 데이터베이스 백업을 생성합니다.
     * 
     * @param string|null $description 백업 설명
     * @param array $tables 백업할 테이블 목록 (비어있으면 모든 테이블)
     * @return array 백업 결과
     */
    public function createBackup(?string $description = null, array $tables = [])
    {
        try {
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $filename = $this->filePrefix . $timestamp . $this->fileExtension;
            $backupFilePath = $this->backupPath . '/' . $filename;
            
            // 데이터베이스 연결 정보
            $connection = config('database.default');
            $config = config("database.connections.{$connection}");
            
            // MySQL 백업 명령 구성
            if ($config['driver'] === 'mysql') {
                $command = $this->buildMysqlBackupCommand($config, $backupFilePath, $tables);
                $result = $this->executeBackupCommand($command, $backupFilePath, $description);
                
                // 오래된 백업 파일 정리
                $this->cleanupOldBackups();
                
                return $result;
            } else {
                throw new \Exception("지원되지 않는 데이터베이스 드라이버: {$config['driver']}");
            }
        } catch (\Exception $e) {
            Log::error("데이터베이스 백업 생성 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * MySQL 백업 명령을 구성합니다.
     * 
     * @param array $config 데이터베이스 설정
     * @param string $backupFilePath 백업 파일 경로
     * @param array $tables 백업할 테이블 목록
     * @return string 백업 명령
     */
    protected function buildMysqlBackupCommand(array $config, string $backupFilePath, array $tables = [])
    {
        $command = "mysqldump";
        
        // 인증 정보 추가
        $command .= " --user={$config['username']}";
        
        if (isset($config['password']) && !empty($config['password'])) {
            $command .= " --password='{$config['password']}'";
        }
        
        // 호스트 및 포트 추가
        $command .= " --host={$config['host']}";
        
        if (isset($config['port']) && !empty($config['port'])) {
            $command .= " --port={$config['port']}";
        }
        
        // 추가 옵션
        $command .= " --single-transaction --skip-lock-tables";
        
        // 데이터베이스 이름 추가
        $command .= " {$config['database']}";
        
        // 특정 테이블만 백업하는 경우
        if (!empty($tables)) {
            $command .= " " . implode(" ", $tables);
        }
        
        // 출력 파일 지정
        $fullPath = Storage::path($backupFilePath);
        $command .= " > {$fullPath}";
        
        return $command;
    }
    
    /**
     * 백업 명령을 실행합니다.
     * 
     * @param string $command 백업 명령
     * @param string $backupFilePath 백업 파일 경로
     * @param string|null $description 백업 설명
     * @return array 실행 결과
     */
    protected function executeBackupCommand(string $command, string $backupFilePath, ?string $description = null)
    {
        try {
            // 명령 실행
            $startTime = microtime(true);
            exec($command, $output, $returnCode);
            $endTime = microtime(true);
            
            // 실행 결과 확인
            if ($returnCode !== 0) {
                throw new \Exception("백업 명령 실행 실패: 종료 코드 {$returnCode}");
            }
            
            // 백업 파일 존재 확인
            if (!Storage::exists($backupFilePath)) {
                throw new \Exception("백업 파일이 생성되지 않았습니다: {$backupFilePath}");
            }
            
            // 백업 파일 크기 및 소요 시간 계산
            $fileSize = Storage::size($backupFilePath);
            $executionTime = round($endTime - $startTime, 2);
            
            // 백업 메타데이터 저장
            $metaFilePath = Str::replaceLast($this->fileExtension, '.json', $backupFilePath);
            $metadata = [
                'filename' => basename($backupFilePath),
                'created_at' => Carbon::now()->toDateTimeString(),
                'size_bytes' => $fileSize,
                'size_formatted' => $this->formatBytes($fileSize),
                'execution_time' => $executionTime,
                'description' => $description,
                'database' => config('database.default'),
                'connection' => config('database.connections.' . config('database.default'))['host']
            ];
            
            Storage::put($metaFilePath, json_encode($metadata, JSON_PRETTY_PRINT));
            
            Log::info("데이터베이스 백업 완료", [
                'file' => $backupFilePath,
                'size' => $this->formatBytes($fileSize),
                'time' => $executionTime
            ]);
            
            return [
                'success' => true,
                'file' => $backupFilePath,
                'metadata' => $metadata
            ];
        } catch (\Exception $e) {
            Log::error("백업 명령 실행 오류: {$e->getMessage()}", [
                'command' => $command,
                'exception' => $e
            ]);
            
            // 실패한 백업 파일 삭제
            if (Storage::exists($backupFilePath)) {
                Storage::delete($backupFilePath);
            }
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 백업 파일을 복원합니다.
     * 
     * @param string $backupFile 백업 파일 경로
     * @return array 복원 결과
     */
    public function restoreBackup(string $backupFile)
    {
        try {
            // 백업 파일 존재 확인
            if (!Storage::exists($backupFile)) {
                throw new \Exception("백업 파일을 찾을 수 없습니다: {$backupFile}");
            }
            
            // 데이터베이스 연결 정보
            $connection = config('database.default');
            $config = config("database.connections.{$connection}");
            
            // MySQL 복원 명령 구성
            if ($config['driver'] === 'mysql') {
                $command = $this->buildMysqlRestoreCommand($config, $backupFile);
                return $this->executeRestoreCommand($command, $backupFile);
            } else {
                throw new \Exception("지원되지 않는 데이터베이스 드라이버: {$config['driver']}");
            }
        } catch (\Exception $e) {
            Log::error("데이터베이스 복원 오류: {$e->getMessage()}", [
                'file' => $backupFile,
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * MySQL 복원 명령을 구성합니다.
     * 
     * @param array $config 데이터베이스 설정
     * @param string $backupFile 백업 파일 경로
     * @return string 복원 명령
     */
    protected function buildMysqlRestoreCommand(array $config, string $backupFile)
    {
        $command = "mysql";
        
        // 인증 정보 추가
        $command .= " --user={$config['username']}";
        
        if (isset($config['password']) && !empty($config['password'])) {
            $command .= " --password='{$config['password']}'";
        }
        
        // 호스트 및 포트 추가
        $command .= " --host={$config['host']}";
        
        if (isset($config['port']) && !empty($config['port'])) {
            $command .= " --port={$config['port']}";
        }
        
        // 데이터베이스 이름 추가
        $command .= " {$config['database']}";
        
        // 입력 파일 지정
        $fullPath = Storage::path($backupFile);
        $command .= " < {$fullPath}";
        
        return $command;
    }
    
    /**
     * 복원 명령을 실행합니다.
     * 
     * @param string $command 복원 명령
     * @param string $backupFile 백업 파일 경로
     * @return array 실행 결과
     */
    protected function executeRestoreCommand(string $command, string $backupFile)
    {
        try {
            // 명령 실행
            $startTime = microtime(true);
            exec($command, $output, $returnCode);
            $endTime = microtime(true);
            
            // 실행 결과 확인
            if ($returnCode !== 0) {
                throw new \Exception("복원 명령 실행 실패: 종료 코드 {$returnCode}");
            }
            
            // 소요 시간 계산
            $executionTime = round($endTime - $startTime, 2);
            
            Log::info("데이터베이스 복원 완료", [
                'file' => $backupFile,
                'time' => $executionTime
            ]);
            
            return [
                'success' => true,
                'file' => $backupFile,
                'execution_time' => $executionTime
            ];
        } catch (\Exception $e) {
            Log::error("복원 명령 실행 오류: {$e->getMessage()}", [
                'command' => $command,
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 백업 파일 목록을 가져옵니다.
     * 
     * @return array 백업 파일 목록
     */
    public function getBackupsList()
    {
        try {
            $this->ensureBackupDirectoryExists();
            
            $files = Storage::files($this->backupPath);
            $backups = [];
            
            foreach ($files as $file) {
                // SQL 파일만 처리
                if (Str::endsWith($file, $this->fileExtension)) {
                    $metaFile = Str::replaceLast($this->fileExtension, '.json', $file);
                    $metadata = [];
                    
                    // 메타데이터 파일이 있으면 읽기
                    if (Storage::exists($metaFile)) {
                        $metadata = json_decode(Storage::get($metaFile), true);
                    } else {
                        // 메타데이터 파일이 없으면 기본 정보 생성
                        $fileSize = Storage::size($file);
                        $filename = basename($file);
                        
                        // 파일명에서 날짜 추출 시도
                        preg_match('/(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})/', $filename, $matches);
                        $createdAt = isset($matches[1]) 
                            ? Carbon::createFromFormat('Y-m-d_H-i-s', $matches[1])->toDateTimeString()
                            : null;
                        
                        $metadata = [
                            'filename' => $filename,
                            'created_at' => $createdAt,
                            'size_bytes' => $fileSize,
                            'size_formatted' => $this->formatBytes($fileSize)
                        ];
                    }
                    
                    $backups[] = array_merge($metadata, [
                        'path' => $file
                    ]);
                }
            }
            
            // 생성일 기준 내림차순 정렬
            usort($backups, function ($a, $b) {
                return strtotime($b['created_at'] ?? 0) - strtotime($a['created_at'] ?? 0);
            });
            
            return [
                'success' => true,
                'backups' => $backups,
                'count' => count($backups)
            ];
        } catch (\Exception $e) {
            Log::error("백업 목록 조회 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'backups' => [],
                'count' => 0
            ];
        }
    }
    
    /**
     * 백업 파일을 삭제합니다.
     * 
     * @param string $backupFile 백업 파일 경로
     * @return array 삭제 결과
     */
    public function deleteBackup(string $backupFile)
    {
        try {
            // 백업 파일 존재 확인
            if (!Storage::exists($backupFile)) {
                throw new \Exception("백업 파일을 찾을 수 없습니다: {$backupFile}");
            }
            
            // 메타데이터 파일 경로
            $metaFile = Str::replaceLast($this->fileExtension, '.json', $backupFile);
            
            // 파일 삭제
            Storage::delete($backupFile);
            
            // 메타데이터 파일이 있으면 삭제
            if (Storage::exists($metaFile)) {
                Storage::delete($metaFile);
            }
            
            Log::info("백업 파일 삭제됨", [
                'file' => $backupFile
            ]);
            
            return [
                'success' => true,
                'file' => $backupFile
            ];
        } catch (\Exception $e) {
            Log::error("백업 파일 삭제 오류: {$e->getMessage()}", [
                'file' => $backupFile,
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 오래된 백업 파일을 정리합니다.
     * 
     * @return array 정리 결과
     */
    public function cleanupOldBackups()
    {
        try {
            $backupsList = $this->getBackupsList();
            
            if (!$backupsList['success']) {
                throw new \Exception("백업 목록 조회 실패: {$backupsList['error']}");
            }
            
            $backups = $backupsList['backups'];
            $deletedFiles = [];
            
            // 최대 백업 파일 수를 초과하는 경우 오래된 파일 삭제
            if (count($backups) > $this->maxBackupFiles) {
                $filesToDelete = array_slice($backups, $this->maxBackupFiles);
                
                foreach ($filesToDelete as $fileInfo) {
                    $result = $this->deleteBackup($fileInfo['path']);
                    
                    if ($result['success']) {
                        $deletedFiles[] = $fileInfo['path'];
                    }
                }
            }
            
            Log::info("오래된 백업 파일 정리 완료", [
                'deleted_count' => count($deletedFiles),
                'remaining_count' => min(count($backups), $this->maxBackupFiles)
            ]);
            
            return [
                'success' => true,
                'deleted_files' => $deletedFiles,
                'deleted_count' => count($deletedFiles),
                'remaining_count' => count($backups) - count($deletedFiles)
            ];
        } catch (\Exception $e) {
            Log::error("백업 파일 정리 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 백업 설정을 업데이트합니다.
     * 
     * @param array $settings 설정 배열
     * @return array 업데이트 결과
     */
    public function updateSettings(array $settings)
    {
        try {
            $updated = [];
            
            if (isset($settings['backup_path'])) {
                $oldPath = $this->backupPath;
                $this->backupPath = $settings['backup_path'];
                $this->ensureBackupDirectoryExists();
                $updated['backup_path'] = ['old' => $oldPath, 'new' => $this->backupPath];
            }
            
            if (isset($settings['file_prefix'])) {
                $oldPrefix = $this->filePrefix;
                $this->filePrefix = $settings['file_prefix'];
                $updated['file_prefix'] = ['old' => $oldPrefix, 'new' => $this->filePrefix];
            }
            
            if (isset($settings['file_extension'])) {
                $oldExtension = $this->fileExtension;
                $this->fileExtension = $settings['file_extension'];
                $updated['file_extension'] = ['old' => $oldExtension, 'new' => $this->fileExtension];
            }
            
            if (isset($settings['max_backup_files'])) {
                $oldMax = $this->maxBackupFiles;
                $this->maxBackupFiles = $settings['max_backup_files'];
                $updated['max_backup_files'] = ['old' => $oldMax, 'new' => $this->maxBackupFiles];
                
                // 최대 백업 파일 수가 변경된 경우 정리 실행
                if ($oldMax != $this->maxBackupFiles) {
                    $this->cleanupOldBackups();
                }
            }
            
            Log::info("백업 설정 업데이트됨", $updated);
            
            return [
                'success' => true,
                'updated' => $updated,
                'current_settings' => $this->getSettings()
            ];
        } catch (\Exception $e) {
            Log::error("백업 설정 업데이트 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 현재 백업 설정을 가져옵니다.
     * 
     * @return array 백업 설정
     */
    public function getSettings()
    {
        return [
            'backup_path' => $this->backupPath,
            'file_prefix' => $this->filePrefix,
            'file_extension' => $this->fileExtension,
            'max_backup_files' => $this->maxBackupFiles
        ];
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