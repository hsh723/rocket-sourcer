<?php

namespace RocketSourcer\Repositories;

use RocketSourcer\Core\Database;
use RocketSourcer\Models\Model;

abstract class Repository implements RepositoryInterface
{
    protected Database $db;
    protected string $modelClass;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->modelClass = $this->getModelClass();
    }

    abstract protected function getModelClass(): string;

    /**
     * 모든 레코드 조회
     *
     * @return array
     */
    public function all(): array
    {
        return $this->createQueryBuilder()->get();
    }

    /**
     * ID로 레코드 조회
     *
     * @param int $id
     * @return Model|null
     */
    public function find($id): ?object
    {
        return $this->createQueryBuilder()->where('id', $id)->first();
    }

    /**
     * 조건으로 레코드 조회
     *
     * @param array $criteria
     * @return array
     */
    public function findBy(array $criteria): array
    {
        $query = $this->createQueryBuilder();
        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }
        return $query->get();
    }

    /**
     * 레코드 생성
     *
     * @param array $data
     * @return Model
     */
    public function create(array $data): object
    {
        return call_user_func([$this->modelClass, 'create'], $data);
    }

    /**
     * 레코드 수정
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, array $data): bool
    {
        $model = $this->find($id);
        if (!$model) {
            return false;
        }

        return $model->update($data);
    }

    /**
     * 레코드 삭제
     *
     * @param int $id
     * @return bool
     */
    public function delete($id): bool
    {
        $model = $this->find($id);
        if (!$model) {
            return false;
        }

        return $model->delete();
    }

    /**
     * 페이지네이션
     *
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function paginate(int $page = 1, int $perPage = 15): array
    {
        $offset = ($page - 1) * $perPage;

        $stmt = $this->db->pdo->prepare(
            "SELECT SQL_CALC_FOUND_ROWS * 
             FROM `{$this->table}` 
             LIMIT :offset, :limit"
        );

        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();

        $items = array_map(function ($row) {
            return new $this->modelClass($row);
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));

        $total = $this->db->pdo->query('SELECT FOUND_ROWS()')->fetchColumn();
        $lastPage = ceil($total / $perPage);

        return [
            'items' => $items,
            'total' => (int)$total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage
        ];
    }

    /**
     * 트랜잭션 시작
     *
     * @return void
     */
    public function beginTransaction(): void
    {
        $this->db->pdo->beginTransaction();
    }

    /**
     * 트랜잭션 커밋
     *
     * @return void
     */
    public function commit(): void
    {
        $this->db->pdo->commit();
    }

    /**
     * 트랜잭션 롤백
     *
     * @return void
     */
    public function rollback(): void
    {
        $this->db->pdo->rollBack();
    }

    /**
     * 쿼리 빌더 생성
     *
     * @return QueryBuilder
     */
    protected function newQuery(): QueryBuilder
    {
        return new QueryBuilder($this->db->pdo, $this->table, $this->modelClass);
    }

    public function findOneBy(array $criteria): ?object
    {
        $query = $this->createQueryBuilder();
        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }
        return $query->first();
    }

    public function count(array $criteria = []): int
    {
        $query = $this->createQueryBuilder();
        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }
        return $query->count();
    }

    public function exists($id): bool
    {
        return $this->find($id) !== null;
    }

    protected function createQueryBuilder()
    {
        /** @var Model $model */
        $model = new $this->modelClass;
        return $model::query();
    }
} 