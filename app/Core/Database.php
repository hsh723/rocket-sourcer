<?php

namespace RocketSourcer\Core;

use PDO;
use PDOException;
use Psr\Log\LoggerInterface;

class Database
{
    private array $config;
    private LoggerInterface $logger;
    private ?PDO $connection = null;

    public function __construct(array $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->connection = $this->createConnection();
        }

        return $this->connection;
    }

    private function createConnection(): PDO
    {
        $config = $this->config['connections'][$this->config['default']];

        try {
            $dsn = sprintf(
                '%s:host=%s;port=%s;dbname=%s;charset=%s',
                $config['driver'],
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );

            return new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $config['options'] ?? []
            );
        } catch (PDOException $e) {
            $this->logger->error('Database connection failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function transaction(callable $callback)
    {
        $connection = $this->getConnection();

        try {
            $connection->beginTransaction();
            $result = $callback($this);
            $connection->commit();
            return $result;
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    public function lastInsertId(): string
    {
        return $this->getConnection()->lastInsertId();
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        return $this->query($sql, $params)->fetch() ?: null;
    }
    
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }
    
    public function insert(string $table, array $data): int
    {
        $fields = array_keys($data);
        $values = array_values($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $fields),
            implode(', ', $placeholders)
        );
        
        $this->query($sql, $values);
        return (int)$this->getConnection()->lastInsertId();
    }
    
    public function update(string $table, array $data, string $where, array $params = []): int
    {
        $set = [];
        foreach ($data as $field => $value) {
            $set[] = "$field = ?";
        }
        
        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $table,
            implode(', ', $set),
            $where
        );
        
        $stmt = $this->query($sql, array_merge(array_values($data), $params));
        return $stmt->rowCount();
    }
    
    public function delete(string $table, string $where, array $params = []): int
    {
        $sql = sprintf('DELETE FROM %s WHERE %s', $table, $where);
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
} 