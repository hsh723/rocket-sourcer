<?php
namespace RocketSourcer\Services\Cache;

use Psr\Log\LoggerInterface;

class FileCache implements CacheInterface
{
    private string $directory;
    private int $defaultTtl;
    private LoggerInterface $logger;
    
    public function __construct(string $directory, int $defaultTtl = 3600, LoggerInterface $logger)
    {
        $this->directory = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR;
        $this->defaultTtl = $defaultTtl;
        $this->logger = $logger;
        
        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0755, true);
        }
    }
    
    public function get(string $key, $default = null)
    {
        $path = $this->getPath($key);
        
        if (!file_exists($path)) {
            return $default;
        }
        
        $data = json_decode(file_get_contents($path), true);
        
        if (!is_array($data) || !isset($data['expires_at']) || !isset($data['value'])) {
            $this->logger->warning('캐시 파일 형식이 잘못되었습니다', ['key' => $key]);
            return $default;
        }
        
        if ($data['expires_at'] < time()) {
            $this->delete($key);
            return $default;
        }
        
        return $data['value'];
    }
    
    public function set(string $key, $value, int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->defaultTtl;
        $expiresAt = time() + $ttl;
        
        $data = [
            'expires_at' => $expiresAt,
            'value' => $value
        ];
        
        $path = $this->getPath($key);
        
        try {
            file_put_contents($path, json_encode($data));
            return true;
        } catch (\Exception $e) {
            $this->logger->error('캐시 설정 실패', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    public function has(string $key): bool
    {
        return $this->get($key, null) !== null;
    }
    
    public function delete(string $key): bool
    {
        $path = $this->getPath($key);
        
        if (file_exists($path)) {
            return unlink($path);
        }
        
        return true;
    }
    
    public function clear(): bool
    {
        $files = glob($this->directory . '*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        return true;
    }
    
    private function getPath(string $key): string
    {
        return $this->directory . md5($key) . '.cache';
    }
}
