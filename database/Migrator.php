<?php

namespace RocketSourcer\Database;

use PDO;

class Migrator
{
    private PDO $pdo;
    private string $path;
    private string $table = 'migrations';

    public function __construct(PDO $pdo, string $path)
    {
        $this->pdo = $pdo;
        $this->path = rtrim($path, '/');
        $this->createMigrationsTable();
    }

    /**
     * 마이그레이션 실행
     *
     * @return array 실행된 마이그레이션 목록
     */
    public function migrate(): array
    {
        $files = $this->getPendingMigrations();
        $executed = [];

        foreach ($files as $file) {
            $migration = $this->getMigrationInstance($file);
            
            try {
                $this->pdo->beginTransaction();
                
                $migration->up();
                $this->logMigration($file);
                
                $this->pdo->commit();
                $executed[] = $file;
                
            } catch (\Exception $e) {
                $this->pdo->rollBack();
                throw new \RuntimeException(
                    "Migration failed: {$file}\n{$e->getMessage()}"
                );
            }
        }

        return $executed;
    }

    /**
     * 마이그레이션 롤백
     *
     * @param int $steps 롤백할 단계 수
     * @return array 롤백된 마이그레이션 목록
     */
    public function rollback(int $steps = 1): array
    {
        $migrations = $this->getExecutedMigrations($steps);
        $rolledBack = [];

        foreach ($migrations as $migration) {
            $instance = $this->getMigrationInstance($migration['migration']);
            
            try {
                $this->pdo->beginTransaction();
                
                $instance->down();
                $this->removeMigrationLog($migration['migration']);
                
                $this->pdo->commit();
                $rolledBack[] = $migration['migration'];
                
            } catch (\Exception $e) {
                $this->pdo->rollBack();
                throw new \RuntimeException(
                    "Rollback failed: {$migration['migration']}\n{$e->getMessage()}"
                );
            }
        }

        return $rolledBack;
    }

    /**
     * 마이그레이션 테이블 생성
     *
     * @return void
     */
    private function createMigrationsTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `migration` varchar(255) NOT NULL,
            `batch` int NOT NULL,
            `executed_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        )";

        $this->pdo->exec($sql);
    }

    /**
     * 대기 중인 마이그레이션 파일 목록 조회
     *
     * @return array
     */
    private function getPendingMigrations(): array
    {
        $files = glob($this->path . '/*.php');
        $executed = $this->getExecutedMigrationNames();
        
        return array_filter($files, function ($file) use ($executed) {
            return !in_array(basename($file), $executed);
        });
    }

    /**
     * 실행된 마이그레이션 조회
     *
     * @param int $limit
     * @return array
     */
    private function getExecutedMigrations(int $limit): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM `{$this->table}` 
             ORDER BY `batch` DESC, `id` DESC 
             LIMIT :limit"
        );
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 실행된 마이그레이션 파일명 목록 조회
     *
     * @return array
     */
    private function getExecutedMigrationNames(): array
    {
        $stmt = $this->pdo->query(
            "SELECT `migration` FROM `{$this->table}`"
        );
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * 마이그레이션 인스턴스 생성
     *
     * @param string $file
     * @return Migration
     */
    private function getMigrationInstance(string $file): Migration
    {
        require_once $file;
        $class = pathinfo($file, PATHINFO_FILENAME);
        return new $class($this->pdo);
    }

    /**
     * 마이그레이션 실행 기록
     *
     * @param string $file
     * @return void
     */
    private function logMigration(string $file): void
    {
        $batch = $this->getNextBatchNumber();
        
        $stmt = $this->pdo->prepare(
            "INSERT INTO `{$this->table}` 
             (`migration`, `batch`) 
             VALUES (:migration, :batch)"
        );
        
        $stmt->execute([
            ':migration' => basename($file),
            ':batch' => $batch
        ]);
    }

    /**
     * 마이그레이션 실행 기록 삭제
     *
     * @param string $file
     * @return void
     */
    private function removeMigrationLog(string $file): void
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM `{$this->table}` 
             WHERE `migration` = :migration"
        );
        
        $stmt->execute([':migration' => $file]);
    }

    /**
     * 다음 배치 번호 조회
     *
     * @return int
     */
    private function getNextBatchNumber(): int
    {
        $stmt = $this->pdo->query(
            "SELECT MAX(`batch`) FROM `{$this->table}`"
        );
        
        return (int)$stmt->fetchColumn() + 1;
    }
} 