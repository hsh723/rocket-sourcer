<?php

namespace RocketSourcer\Core;

use Psr\Log\LoggerInterface;
use RocketSourcer\Core\Http\Response;
use RocketSourcer\Core\Exceptions\HttpException;

class ErrorHandler
{
    private LoggerInterface $logger;
    private bool $debug;
    
    public function __construct(LoggerInterface $logger, bool $debug = false)
    {
        $this->logger = $logger;
        $this->debug = $debug;
    }
    
    public function register(): void
    {
        set_exception_handler([$this, 'handleException']);
        set_error_handler([$this, 'handleError']);
        register_shutdown_function([$this, 'handleShutdown']);
    }
    
    public function handleException(\Throwable $exception): Response
    {
        $statusCode = $exception instanceof HttpException 
            ? $exception->getCode() 
            : 500;
        
        $this->logger->error($exception->getMessage(), [
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        $response = [
            'error' => [
                'message' => $this->debug 
                    ? $exception->getMessage() 
                    : '서버 오류가 발생했습니다',
                'code' => $statusCode
            ]
        ];
        
        if ($this->debug) {
            $response['error']['file'] = $exception->getFile();
            $response['error']['line'] = $exception->getLine();
            $response['error']['trace'] = explode("\n", $exception->getTraceAsString());
        }
        
        return new Response(
            json_encode($response),
            $statusCode,
            ['Content-Type' => 'application/json']
        );
    }
    
    public function handleError(int $level, string $message, string $file, int $line): bool
    {
        throw new \ErrorException($message, 0, $level, $file, $line);
    }
    
    public function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $this->logger->critical('치명적 오류: ' . $error['message'], [
                'file' => $error['file'],
                'line' => $error['line']
            ]);
        }
    }
}
