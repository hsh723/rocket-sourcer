<?php

namespace RocketSourcer\Database;

use RocketSourcer\Core\Database;

abstract class Migration
{
    protected Database $db;
    protected string $table;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    abstract public function up(): void;
    abstract public function down(): void;

    protected function createTable(string $table, callable $callback): void
    {
        $schema = new SchemaBuilder($table);
        $callback($schema);

        $this->db->query($schema->toSql());
    }

    protected function dropTable(string $table): void
    {
        $this->db->query("DROP TABLE IF EXISTS {$table}");
    }

    protected function addColumn(string $table, string $column, string $type, array $options = []): void
    {
        $sql = "ALTER TABLE {$table} ADD COLUMN {$column} {$type}";
        
        if (!empty($options['nullable'])) {
            $sql .= " NULL";
        } else {
            $sql .= " NOT NULL";
        }

        if (isset($options['default'])) {
            $sql .= " DEFAULT " . $this->getDefaultValue($options['default']);
        }

        if (!empty($options['unique'])) {
            $sql .= " UNIQUE";
        }

        if (!empty($options['index'])) {
            $sql .= ", ADD INDEX `idx_{$table}_{$column}` (`{$column}`)";
        }

        $this->db->query($sql);
    }

    protected function dropColumn(string $table, string $column): void
    {
        $this->db->query("ALTER TABLE {$table} DROP COLUMN {$column}");
    }

    protected function addForeignKey(string $table, string $column, string $referenceTable, string $referenceColumn = 'id'): void
    {
        $constraintName = "fk_{$table}_{$column}";
        $sql = "ALTER TABLE {$table} ADD CONSTRAINT {$constraintName} 
                FOREIGN KEY ({$column}) REFERENCES {$referenceTable}({$referenceColumn})
                ON DELETE CASCADE ON UPDATE CASCADE";
        
        $this->db->query($sql);
    }

    protected function dropForeignKey(string $table, string $column): void
    {
        $constraintName = "fk_{$table}_{$column}";
        $this->db->query("ALTER TABLE {$table} DROP FOREIGN KEY {$constraintName}");
    }

    protected function addIndex(string $table, string $column, bool $unique = false): void
    {
        $type = $unique ? 'UNIQUE INDEX' : 'INDEX';
        $indexName = ($unique ? 'unq_' : 'idx_') . "{$table}_{$column}";
        $this->db->query("ALTER TABLE {$table} ADD {$type} {$indexName} ({$column})");
    }

    protected function dropIndex(string $table, string $column, bool $unique = false): void
    {
        $indexName = ($unique ? 'unq_' : 'idx_') . "{$table}_{$column}";
        $this->db->query("ALTER TABLE {$table} DROP INDEX {$indexName}");
    }

    private function getDefaultValue($value): string
    {
        if (is_null($value)) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string)$value;
        }

        return "'" . addslashes($value) . "'";
    }
} 