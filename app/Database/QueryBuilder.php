<?php

namespace RocketSourcer\Database;

use RocketSourcer\Core\Database;

class QueryBuilder
{
    private Database $db;
    private string $model;
    private string $table;
    private array $selects = ['*'];
    private array $wheres = [];
    private array $orderBy = [];
    private array $groupBy = [];
    private array $having = [];
    private array $joins = [];
    private array $bindings = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private array $sets = [];

    public function __construct(Database $db, string $model, string $table)
    {
        $this->db = $db;
        $this->model = $model;
        $this->table = $table;
    }

    public function select($columns): self
    {
        $this->selects = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    public function where($column, $operator = null, $value = null): self
    {
        if (is_array($column)) {
            foreach ($column as $key => $value) {
                $this->where($key, '=', $value);
            }
            return $this;
        }

        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value
        ];

        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values
        ];

        return $this;
    }

    public function whereNull(string $column): self
    {
        $this->wheres[] = [
            'type' => 'null',
            'column' => $column
        ];

        return $this;
    }

    public function whereNotNull(string $column): self
    {
        $this->wheres[] = [
            'type' => 'not_null',
            'column' => $column
        ];

        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->orderBy[] = [
            'column' => $column,
            'direction' => strtoupper($direction)
        ];

        return $this;
    }

    public function groupBy($columns): self
    {
        $this->groupBy = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    public function having(string $column, string $operator, $value): self
    {
        $this->having[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value
        ];

        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = [
            'type' => 'inner',
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second
        ];

        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = [
            'type' => 'left',
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second
        ];

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function set(string $column, $value): self
    {
        $this->sets[$column] = $value;
        return $this;
    }

    public function get(): array
    {
        $sql = $this->toSql();
        $result = $this->db->query($sql, $this->bindings)->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(function ($row) {
            return new $this->model($row);
        }, $result);
    }

    public function first(): ?object
    {
        $this->limit(1);
        $result = $this->get();
        return $result[0] ?? null;
    }

    public function insert(array $values): ?int
    {
        $columns = array_keys($values);
        $parameters = array_map(fn($column) => ":{$column}", $columns);

        $sql = "INSERT INTO {$this->table} ("
            . implode(', ', array_map(fn($column) => "`{$column}`", $columns))
            . ") VALUES ("
            . implode(', ', $parameters)
            . ")";

        $this->bindings = array_combine($parameters, array_values($values));
        $this->db->query($sql, $this->bindings);

        return $this->db->lastInsertId();
    }

    public function update(): bool
    {
        if (empty($this->sets)) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET "
            . implode(', ', array_map(function ($column) {
                return "`{$column}` = :{$column}";
            }, array_keys($this->sets)));

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->compileWhere();
        }

        $this->bindings = array_merge(
            array_combine(
                array_map(fn($column) => ":{$column}", array_keys($this->sets)),
                array_values($this->sets)
            ),
            $this->bindings
        );

        return $this->db->query($sql, $this->bindings)->rowCount() > 0;
    }

    public function delete(): bool
    {
        $sql = "DELETE FROM {$this->table}";

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->compileWhere();
        }

        return $this->db->query($sql, $this->bindings)->rowCount() > 0;
    }

    public function count(): int
    {
        $this->selects = ['COUNT(*) as count'];
        $result = $this->db->query($this->toSql(), $this->bindings)->fetch(\PDO::FETCH_ASSOC);
        return (int)$result['count'];
    }

    private function toSql(): string
    {
        $sql = "SELECT " . implode(', ', $this->selects) . " FROM {$this->table}";

        if (!empty($this->joins)) {
            $sql .= ' ' . $this->compileJoins();
        }

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->compileWhere();
        }

        if (!empty($this->groupBy)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groupBy);
        }

        if (!empty($this->having)) {
            $sql .= ' HAVING ' . $this->compileHaving();
        }

        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ' . $this->compileOrderBy();
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }

    private function compileWhere(): string
    {
        return implode(' AND ', array_map(function ($where) {
            switch ($where['type']) {
                case 'basic':
                    $param = ':where_' . count($this->bindings);
                    $this->bindings[$param] = $where['value'];
                    return "`{$where['column']}` {$where['operator']} {$param}";

                case 'in':
                    $params = [];
                    foreach ($where['values'] as $value) {
                        $param = ':where_' . count($this->bindings);
                        $this->bindings[$param] = $value;
                        $params[] = $param;
                    }
                    return "`{$where['column']}` IN (" . implode(', ', $params) . ")";

                case 'null':
                    return "`{$where['column']}` IS NULL";

                case 'not_null':
                    return "`{$where['column']}` IS NOT NULL";
            }
        }, $this->wheres));
    }

    private function compileJoins(): string
    {
        return implode(' ', array_map(function ($join) {
            $type = strtoupper($join['type']);
            return "{$type} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
        }, $this->joins));
    }

    private function compileOrderBy(): string
    {
        return implode(', ', array_map(function ($order) {
            return "{$order['column']} {$order['direction']}";
        }, $this->orderBy));
    }

    private function compileHaving(): string
    {
        return implode(' AND ', array_map(function ($having) {
            $param = ':having_' . count($this->bindings);
            $this->bindings[$param] = $having['value'];
            return "{$having['column']} {$having['operator']} {$param}";
        }, $this->having));
    }
} 