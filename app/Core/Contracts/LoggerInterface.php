<?php

namespace RocketSourcer\Core\Contracts;

interface LoggerInterface
{
    /**
     * 디버그 로그 기록
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function debug(string $message, array $context = []): void;
    
    /**
     * 정보 로그 기록
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function info(string $message, array $context = []): void;
    
    /**
     * 경고 로그 기록
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function warning(string $message, array $context = []): void;
    
    /**
     * 에러 로그 기록
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function error(string $message, array $context = []): void;
    
    /**
     * 로그 파일 경로 설정
     *
     * @param string $path
     * @return void
     */
    public function setPath(string $path): void;
    
    /**
     * 로그 레벨 설정
     *
     * @param string $level
     * @return void
     */
    public function setLevel(string $level): void;
} 