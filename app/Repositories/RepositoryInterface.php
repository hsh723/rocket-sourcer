<?php

namespace RocketSourcer\Repositories;

interface RepositoryInterface
{
    /**
     * 모든 레코드 조회
     *
     * @return array
     */
    public function all(): array;

    /**
     * ID로 레코드 조회
     *
     * @param int $id
     * @return mixed
     */
    public function find($id): ?object;

    /**
     * 조건으로 레코드 조회
     *
     * @param array $criteria
     * @return array
     */
    public function findBy(array $criteria): array;

    /**
     * 레코드 생성
     *
     * @param array $data
     * @return mixed
     */
    public function create(array $data): object;

    /**
     * 레코드 수정
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, array $data): bool;

    /**
     * 레코드 삭제
     *
     * @param int $id
     * @return bool
     */
    public function delete($id): bool;

    /**
     * 페이지네이션
     *
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function paginate(int $page = 1, int $perPage = 15): array;

    /**
     * 트랜잭션 시작
     *
     * @return void
     */
    public function beginTransaction(): void;

    /**
     * 트랜잭션 커밋
     *
     * @return void
     */
    public function commit(): void;

    /**
     * 트랜잭션 롤백
     *
     * @return void
     */
    public function rollback(): void;

    /**
     * 조건으로 레코드 개수 조회
     *
     * @param array $criteria
     * @return int
     */
    public function count(array $criteria = []): int;

    /**
     * ID로 레코드 존재 여부 조회
     *
     * @param int $id
     * @return bool
     */
    public function exists($id): bool;
} 