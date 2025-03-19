<?php

namespace RocketSourcer\Core\Middleware;

use RocketSourcer\Core\Http\Request;
use RocketSourcer\Core\Http\Response;
use RocketSourcer\Services\Auth\AuthService;
use Psr\Log\LoggerInterface;

class ApiKeyMiddleware implements MiddlewareInterface
{
    private AuthService $authService;
    private LoggerInterface $logger;
    
    public function __construct(AuthService $authService, LoggerInterface $logger)
    {
        $this->authService = $authService;
        $this->logger = $logger;
    }
    
    public function process(Request $request, callable $next): Response
    {
        $apiKey = $request->getHeader('X-API-Key');
        
        if (!$apiKey) {
            $this->logger->warning('API 인증 실패: API 키 없음');
            return new Response(
                json_encode(['error' => 'API 키가 필요합니다']),
                401,
                ['Content-Type' => 'application/json']
            );
        }
        
        $user = $this->authService->authenticateByApiKey($apiKey);
        
        if (!$user) {
            $this->logger->warning('API 인증 실패: 유효하지 않은 API 키');
            return new Response(
                json_encode(['error' => '유효하지 않은 API 키입니다']),
                401,
                ['Content-Type' => 'application/json']
            );
        }
        
        return $next($request);
    }
}
