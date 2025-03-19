<?php

namespace RocketSourcer\Core\Contracts;

interface RouterInterface
{
    /**
     * HTTP GET 라우트 등록
     *
     * @param string $pattern 라우트 패턴
     * @param mixed $handler 핸들러
     * @return void
     */
    public function get(string $pattern, $handler): void;
    
    /**
     * HTTP POST 라우트 등록
     *
     * @param string $pattern 라우트 패턴
     * @param mixed $handler 핸들러
     * @return void
     */
    public function post(string $pattern, $handler): void;
    
    /**
     * 미들웨어 등록
     *
     * @param string $pattern 라우트 패턴
     * @param callable $middleware 미들웨어 함수
     * @return void
     */
    public function middleware(string $pattern, callable $middleware): void;
    
    /**
     * 요청 처리
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function dispatch(RequestInterface $request): ResponseInterface;
} 