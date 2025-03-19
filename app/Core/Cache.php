<?php

namespace RocketSourcer\Core;

use RocketSourcer\Core\Contracts\CacheInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class Cache implements CacheInterface
{
    private string $path;
    private int $defaultTtl;
    private LoggerInterface $logger;
    private array $cache = [];
    private array $drivers = ['file', 'memory'];
    private string $driver;
    
    public function __construct(string $path, int $defaultTtl = 3600, LoggerInterface $logger)
    {
        $this->path = rtrim($path, '/');
        $this->defaultTtl = $defaultTtl;
        $this->logger = $logger;
        $this->driver = $config['driver'] ?? 'file';
        
        if (!is_dir($this->path)) {
            mkdir($this->path, 0777, true);
        }

        if (!in_array($this->driver, $this->drivers)) {
            throw new RuntimeException("Unsupported cache driver: {$this->driver}");
        }
    }
    
    /**
     * 캐시 데이터 저장
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl 초 단위 만료 시간
     * @return bool
     */
    public function set(string $key, $value, ?int $ttl = null): bool
    {
        try {
            if ($this->driver === 'memory') {
                return $this->setInMemory($key, $value, $ttl ?? $this->defaultTtl);
            }
            return $this->setInFile($key, $value, $ttl ?? $this->defaultTtl);
        } catch (\Throwable $e) {
            $this->logger->error('Cache set failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * 캐시 데이터 조회
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        if ($this->driver === 'memory') {
            return $this->getFromMemory($key, $default);
        }
        return $this->getFromFile($key, $default);
    }
    
    /**
     * 캐시 데이터 삭제
     *
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        try {
            if ($this->driver === 'memory') {
                unset($this->cache[$key]);
                return true;
            }

            $file = $this->getFilePath($key);
            if (file_exists($file)) {
                return unlink($file);
            }
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Cache delete failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * 캐시 데이터 존재 여부 확인
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        if ($this->driver === 'memory') {
            return isset($this->cache[$key]) && $this->cache[$key]['expires'] > time();
        }
        
        $file = $this->getFilePath($key);
        if (!file_exists($file)) {
            return false;
        }

        $data = $this->readFile($file);
        return $data !== null && $data['expires'] > time();
    }
    
    /**
     * 모든 캐시 데이터 삭제
     *
     * @return bool
     */
    public function clear(): bool
    {
        try {
            if ($this->driver === 'memory') {
                $this->cache = [];
                return true;
            }

            $files = glob($this->path . '/*.cache');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Cache clear failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * 만료된 캐시 데이터 정리
     *
     * @return bool
     */
    public function gc(): bool
    {
        $files = glob($this->path . '/*');
        
        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }
            
            $data = unserialize(file_get_contents($file));
            
            if ($data['expires_at'] < time()) {
                unlink($file);
            }
        }
        
        return true;
    }
    
    /**
     * 캐시 파일 경로 생성
     *
     * @param string $key
     * @return string
     */
    private function getFilePath(string $key): string
    {
        return $this->path . '/' . md5($key) . '.cache';
    }

    private function getFromMemory(string $key, $default = null)
    {
        if (!isset($this->cache[$key])) {
            return $default;
        }

        $data = $this->cache[$key];
        if ($data['expires'] <= time()) {
            unset($this->cache[$key]);
            return $default;
        }

        return $data['value'];
    }

    private function setInMemory(string $key, $value, int $ttl): bool
    {
        $this->cache[$key] = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        return true;
    }

    private function getFromFile(string $key, $default = null)
    {
        $file = $this->getFilePath($key);
        if (!file_exists($file)) {
            return $default;
        }

        $data = $this->readFile($file);
        if ($data === null || $data['expires'] <= time()) {
            @unlink($file);
            return $default;
        }

        return $data['value'];
    }

    private function setInFile(string $key, $value, int $ttl): bool
    {
        $file = $this->getFilePath($key);
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];

        return file_put_contents($file, serialize($data)) !== false;
    }

    private function readFile(string $file)
    {
        $content = file_get_contents($file);
        if ($content === false) {
            return null;
        }

        $data = @unserialize($content);
        if ($data === false) {
            return null;
        }

        return $data;
    }

    public function remember(string $key, int $ttl, callable $callback)
    {
        if ($this->has($key)) {
            return $this->get($key);
        }

        $value = $callback();
        $this->set($key, $value, $ttl);
        return $value;
    }

    public function forever(string $key, $value): bool
    {
        return $this->set($key, $value, 60 * 60 * 24 * 365 * 10); // 10 years
    }

    public function flush(): bool
    {
        return $this->clear();
    }

    public function increment(string $key, int $value = 1): int
    {
        $current = (int)$this->get($key, 0);
        $new = $current + $value;
        $this->set($key, $new);
        return $new;
    }

    public function decrement(string $key, int $value = 1): int
    {
        return $this->increment($key, -$value);
    }
} 