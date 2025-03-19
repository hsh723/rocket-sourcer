<?php

namespace RocketSourcer\Core\Contracts;

interface CacheInterface
{
    /**
     * 캐시 데이터 저장
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl 초 단위 만료 시간
     * @return bool
     */
    public function set(string $key, $value, ?int $ttl = null): bool;
    
    /**
     * 캐시 데이터 조회
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null);
    
    /**
     * 캐시 데이터 삭제
     *
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool;
    
    /**
     * 캐시 데이터 존재 여부 확인
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;
    
    /**
     * 모든 캐시 데이터 삭제
     *
     * @return bool
     */
    public function clear(): bool;
    
    /**
     * 만료된 캐시 데이터 정리
     *
     * @return bool
     */
    public function gc(): bool;
} 