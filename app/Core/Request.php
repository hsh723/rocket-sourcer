<?php

namespace RocketSourcer\Core;

use RocketSourcer\Core\Contracts\RequestInterface;

class Request implements RequestInterface
{
    private array $query;
    private array $post;
    private array $server;
    private array $headers;
    private ?array $json = null;
    
    public function __construct()
    {
        $this->query = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
        $this->headers = $this->parseHeaders();
    }
    
    /**
     * GET 파라미터 조회
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function query(string $key, $default = null)
    {
        return $this->query[$key] ?? $default;
    }
    
    /**
     * POST 파라미터 조회
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function post(string $key, $default = null)
    {
        return $this->post[$key] ?? $default;
    }
    
    /**
     * 요청 메서드 조회
     *
     * @return string
     */
    public function method(): string
    {
        return $this->server['REQUEST_METHOD'];
    }
    
    /**
     * 요청 URI 조회
     *
     * @return string
     */
    public function uri(): string
    {
        $uri = $this->server['REQUEST_URI'];
        
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        
        return rawurldecode($uri);
    }
    
    /**
     * 헤더 조회
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function header(string $key, $default = null)
    {
        $key = strtolower($key);
        return $this->headers[$key] ?? $default;
    }
    
    /**
     * JSON 요청 본문 조회
     *
     * @return array
     */
    public function json(): array
    {
        if ($this->json === null) {
            $content = file_get_contents('php://input');
            $this->json = json_decode($content, true) ?? [];
        }
        
        return $this->json;
    }
    
    /**
     * 입력 유효성 검사
     *
     * @param array $rules
     * @return array
     * @throws \InvalidArgumentException
     */
    public function validate(array $rules): array
    {
        $data = array_merge($this->query, $this->post, $this->json());
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            if (!isset($data[$field])) {
                if (strpos($rule, 'required') !== false) {
                    $errors[$field] = "$field is required";
                }
                continue;
            }
            
            $value = $data[$field];
            
            foreach (explode('|', $rule) as $constraint) {
                if (!$this->validateConstraint($constraint, $value)) {
                    $errors[$field] = "$field failed $constraint validation";
                }
            }
        }
        
        if (!empty($errors)) {
            throw new \InvalidArgumentException(json_encode($errors));
        }
        
        return $data;
    }
    
    /**
     * 헤더 파싱
     *
     * @return array
     */
    private function parseHeaders(): array
    {
        $headers = [];
        
        foreach ($this->server as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $name = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$name] = $value;
            }
        }
        
        return $headers;
    }
    
    /**
     * 제약 조건 검증
     *
     * @param string $constraint
     * @param mixed $value
     * @return bool
     */
    private function validateConstraint(string $constraint, $value): bool
    {
        switch ($constraint) {
            case 'required':
                return !empty($value);
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            case 'numeric':
                return is_numeric($value);
            case 'array':
                return is_array($value);
            default:
                if (preg_match('/min:(\d+)/', $constraint, $matches)) {
                    return strlen($value) >= (int)$matches[1];
                }
                if (preg_match('/max:(\d+)/', $constraint, $matches)) {
                    return strlen($value) <= (int)$matches[1];
                }
                return true;
        }
    }
} 