<?php

namespace RocketSourcer\Database;

class SchemaBuilder
{
    private string $table;
    private array $columns = [];
    private array $indexes = [];
    private array $foreignKeys = [];
    private ?string $primaryKey = null;
    private array $options = [
        'engine' => 'InnoDB',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci'
    ];

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function id(string $column = 'id'): self
    {
        $this->columns[] = "{$column} BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY";
        $this->primaryKey = $column;
        return $this;
    }

    public function string(string $column, int $length = 255, bool $nullable = false): self
    {
        $this->addColumn($column, "VARCHAR({$length})", $nullable);
        return $this;
    }

    public function text(string $column, bool $nullable = false): self
    {
        $this->addColumn($column, "TEXT", $nullable);
        return $this;
    }

    public function integer(string $column, bool $nullable = false): self
    {
        $this->addColumn($column, "INT", $nullable);
        return $this;
    }

    public function bigInteger(string $column, bool $nullable = false): self
    {
        $this->addColumn($column, "BIGINT", $nullable);
        return $this;
    }

    public function boolean(string $column, bool $nullable = false): self
    {
        $this->addColumn($column, "TINYINT(1)", $nullable);
        return $this;
    }

    public function decimal(string $column, int $precision = 8, int $scale = 2, bool $nullable = false): self
    {
        $this->addColumn($column, "DECIMAL({$precision},{$scale})", $nullable);
        return $this;
    }

    public function datetime(string $column, bool $nullable = false): self
    {
        $this->addColumn($column, "DATETIME", $nullable);
        return $this;
    }

    public function timestamp(string $column, bool $nullable = false): self
    {
        $this->addColumn($column, "TIMESTAMP", $nullable);
        return $this;
    }

    public function timestamps(): self
    {
        $this->timestamp('created_at')->nullable();
        $this->timestamp('updated_at')->nullable();
        return $this;
    }

    public function softDeletes(): self
    {
        $this->timestamp('deleted_at')->nullable();
        return $this;
    }

    public function json(string $column, bool $nullable = false): self
    {
        $this->addColumn($column, "JSON", $nullable);
        return $this;
    }

    public function enum(string $column, array $values, bool $nullable = false): self
    {
        $values = array_map(fn($value) => "'" . addslashes($value) . "'", $values);
        $this->addColumn($column, "ENUM(" . implode(',', $values) . ")", $nullable);
        return $this;
    }

    public function index(string $column): self
    {
        $this->indexes[] = "INDEX `idx_{$this->table}_{$column}` (`{$column}`)";
        return $this;
    }

    public function unique(string $column): self
    {
        $this->indexes[] = "UNIQUE INDEX `unq_{$this->table}_{$column}` (`{$column}`)";
        return $this;
    }

    public function foreignKey(string $column, string $referenceTable, string $referenceColumn = 'id'): self
    {
        $constraintName = "fk_{$this->table}_{$column}";
        $this->foreignKeys[] = "CONSTRAINT `{$constraintName}` FOREIGN KEY (`{$column}`) 
            REFERENCES `{$referenceTable}`(`{$referenceColumn}`) ON DELETE CASCADE ON UPDATE CASCADE";
        return $this;
    }

    public function nullable(): self
    {
        $lastColumn = array_key_last($this->columns);
        if ($lastColumn !== null) {
            $this->columns[$lastColumn] = str_replace('NOT NULL', 'NULL', $this->columns[$lastColumn]);
        }
        return $this;
    }

    public function default($value): self
    {
        $lastColumn = array_key_last($this->columns);
        if ($lastColumn !== null) {
            $defaultValue = $this->getDefaultValue($value);
            $this->columns[$lastColumn] .= " DEFAULT {$defaultValue}";
        }
        return $this;
    }

    public function engine(string $engine): self
    {
        $this->options['engine'] = $engine;
        return $this;
    }

    public function charset(string $charset): self
    {
        $this->options['charset'] = $charset;
        return $this;
    }

    public function collation(string $collation): self
    {
        $this->options['collation'] = $collation;
        return $this;
    }

    public function toSql(): string
    {
        $columns = array_merge($this->columns, $this->indexes, $this->foreignKeys);
        
        return "CREATE TABLE `{$this->table}` (\n    " 
            . implode(",\n    ", $columns) 
            . "\n) ENGINE={$this->options['engine']} "
            . "DEFAULT CHARSET={$this->options['charset']} "
            . "COLLATE={$this->options['collation']}";
    }

    private function addColumn(string $column, string $type, bool $nullable): void
    {
        $this->columns[] = "`{$column}` {$type} " . ($nullable ? "NULL" : "NOT NULL");
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