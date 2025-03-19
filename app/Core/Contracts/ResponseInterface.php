<?php

namespace RocketSourcer\Core\Contracts;

interface ResponseInterface
{
    /**
     * JSON 응답 설정
     *
     * @param mixed $data
     * @param int $status
     * @return static
     */
    public function json($data, int $status = 200): self;
    
    /**
     * HTML 응답 설정
     *
     * @param string $html
     * @param int $status
     * @return static
     */
    public function html(string $html, int $status = 200): self;
    
    /**
     * 상태 코드 설정
     *
     * @param int $status
     * @return static
     */
    public function status(int $status): self;
    
    /**
     * 헤더 설정
     *
     * @param string $key
     * @param string $value
     * @return static
     */
    public function header(string $key, string $value): self;
    
    /**
     * 응답 전송
     *
     * @return void
     */
    public function send(): void;
} 