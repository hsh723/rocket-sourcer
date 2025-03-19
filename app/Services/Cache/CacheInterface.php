<?php
namespace RocketSourcer\Services\Cache;

interface CacheInterface
{
    public function get(string $key, $default = null);
    public function set(string $key, $value, int $ttl = null): bool;
    public function has(string $key): bool;
    public function delete(string $key): bool;
    public function clear(): bool;
}
