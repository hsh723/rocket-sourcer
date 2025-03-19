<?php
namespace RocketSourcer\Core;

class Config
{
    private array $config = [];
    
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }
    
    public function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $config = $this->config;
        
        foreach ($keys as $segment) {
            if (!isset($config[$segment])) {
                return $default;
            }
            $config = $config[$segment];
        }
        
        return $config;
    }
    
    public function set(string $key, $value): void
    {
        $keys = explode('.', $key);
        $this->setValue($this->config, $keys, $value);
    }
    
    private function setValue(array &$array, array $keys, $value): void
    {
        $key = array_shift($keys);
        
        if (empty($keys)) {
            $array[$key] = $value;
            return;
        }
        
        if (!isset($array[$key]) || !is_array($array[$key])) {
            $array[$key] = [];
        }
        
        $this->setValue($array[$key], $keys, $value);
    }
    
    public function all(): array
    {
        return $this->config;
    }
    
    public static function load(string $path): self
    {
        if (!file_exists($path)) {
            throw new \InvalidArgumentException("설정 파일을 찾을 수 없습니다: {$path}");
        }
        
        $config = require $path;
        
        if (!is_array($config)) {
            throw new \InvalidArgumentException("설정 파일은 배열을 반환해야 합니다: {$path}");
        }
        
        return new self($config);
    }
}
