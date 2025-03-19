<?php

namespace RocketSourcer\Core;

use RocketSourcer\Core\Contracts\ResponseInterface;

class Response implements ResponseInterface
{
    private int $status = 200;
    private array $headers = [];
    private string $content = '';
    private string $contentType = 'text/html';
    
    /**
     * JSON 응답 설정
     *
     * @param mixed $data
     * @param int $status
     * @return static
     */
    public function json($data, int $status = 200): self
    {
        $this->content = json_encode($data, JSON_UNESCAPED_UNICODE);
        $this->contentType = 'application/json';
        $this->status = $status;
        
        return $this;
    }
    
    /**
     * HTML 응답 설정
     *
     * @param string $html
     * @param int $status
     * @return static
     */
    public function html(string $html, int $status = 200): self
    {
        $this->content = $html;
        $this->contentType = 'text/html';
        $this->status = $status;
        
        return $this;
    }
    
    /**
     * 상태 코드 설정
     *
     * @param int $status
     * @return static
     */
    public function status(int $status): self
    {
        $this->status = $status;
        return $this;
    }
    
    /**
     * 헤더 설정
     *
     * @param string $key
     * @param string $value
     * @return static
     */
    public function header(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }
    
    /**
     * 응답 전송
     *
     * @return void
     */
    public function send(): void
    {
        if (!headers_sent()) {
            // 상태 코드 설정
            http_response_code($this->status);
            
            // Content-Type 헤더 설정
            header('Content-Type: ' . $this->contentType . '; charset=UTF-8');
            
            // 추가 헤더 설정
            foreach ($this->headers as $key => $value) {
                header($key . ': ' . $value);
            }
        }
        
        echo $this->content;
    }
    
    /**
     * 성공 응답 생성
     *
     * @param mixed $data
     * @param string $message
     * @param int $status
     * @return static
     */
    public static function success($data = null, string $message = 'Success', int $status = 200): self
    {
        return (new self())->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }
    
    /**
     * 에러 응답 생성
     *
     * @param string $message
     * @param int $status
     * @param mixed $errors
     * @return static
     */
    public static function error(string $message, int $status = 400, $errors = null): self
    {
        return (new self())->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $status);
    }
} 