<?php

namespace RocketSourcer\Core\Contracts;

interface RequestInterface
{
    /**
     * GET 파라미터 조회
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function query(string $key, $default = null);
    
    /**
     * POST 파라미터 조회
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function post(string $key, $default = null);
    
    /**
     * 요청 메서드 조회
     *
     * @return string
     */
    public function method(): string;
    
    /**
     * 요청 URI 조회
     *
     * @return string
     */
    public function uri(): string;
    
    /**
     * 헤더 조회
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function header(string $key, $default = null);
    
    /**
     * JSON 요청 본문 조회
     *
     * @return array
     */
    public function json(): array;
    
    /**
     * 입력 유효성 검사
     *
     * @param array $rules
     * @return array
     */
    public function validate(array $rules): array;
} 