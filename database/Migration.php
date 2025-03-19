<?php

namespace RocketSourcer\Database;

use PDO;

abstract class Migration
{
    protected PDO $pdo;
    protected string $table;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * 마이그레이션 실행
     *
     * @return void
     */
    abstract public function up(): void;

    /**
     * 마이그레이션 롤백
     *
     * @return void
     */
    abstract public function down(): void;

    /**
     * 테이블 생성
     *
     * @param string $sql
     * @return bool
     */
    protected function createTable(string $sql): bool
    {
        return $this->pdo->exec($sql) !== false;
    }

    /**
     * 테이블 삭제
     *
     * @param string $table
     * @return bool
     */
    protected function dropTable(string $table): bool
    {
        return $this->pdo->exec("DROP TABLE IF EXISTS `{$table}`") !== false;
    }

    /**
     * 외래 키 제약 조건 추가
     *
     * @param string $table
     * @param string $column
     * @param string $referenceTable
     * @param string $referenceColumn
     * @param string $onDelete
     * @return bool
     */
    protected function addForeignKey(
        string $table,
        string $column,
        string $referenceTable,
        string $referenceColumn = 'id',
        string $onDelete = 'CASCADE'
    ): bool {
        $sql = "ALTER TABLE `{$table}` 
                ADD CONSTRAINT `{$table}_{$column}_foreign` 
                FOREIGN KEY (`{$column}`) 
                REFERENCES `{$referenceTable}`(`{$referenceColumn}`) 
                ON DELETE {$onDelete}";

        return $this->pdo->exec($sql) !== false;
    }

    /**
     * 외래 키 제약 조건 삭제
     *
     * @param string $table
     * @param string $column
     * @return bool
     */
    protected function dropForeignKey(string $table, string $column): bool
    {
        $sql = "ALTER TABLE `{$table}` 
                DROP FOREIGN KEY `{$table}_{$column}_foreign`";

        return $this->pdo->exec($sql) !== false;
    }

    /**
     * 인덱스 추가
     *
     * @param string $table
     * @param string|array $columns
     * @param bool $unique
     * @return bool
     */
    protected function addIndex(string $table, $columns, bool $unique = false): bool
    {
        $columns = is_array($columns) ? $columns : [$columns];
        $type = $unique ? 'UNIQUE' : '';
        $name = implode('_', $columns);
        
        $sql = "CREATE {$type} INDEX `{$table}_{$name}_index` 
                ON `{$table}` (`" . implode('`, `', $columns) . "`)";

        return $this->pdo->exec($sql) !== false;
    }

    /**
     * 인덱스 삭제
     *
     * @param string $table
     * @param string|array $columns
     * @return bool
     */
    protected function dropIndex(string $table, $columns): bool
    {
        $columns = is_array($columns) ? $columns : [$columns];
        $name = implode('_', $columns);
        
        $sql = "DROP INDEX `{$table}_{$name}_index` ON `{$table}`";

        return $this->pdo->exec($sql) !== false;
    }
} 