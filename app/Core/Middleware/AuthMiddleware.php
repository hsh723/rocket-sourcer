<?php

namespace RocketSourcer\Core\Middleware;

use RocketSourcer\Core\Http\Request;
use RocketSourcer\Core\Http\Response;
use RocketSourcer\Services\Auth\AuthService;
use Psr\Log\LoggerInterface;

class AuthMiddleware implements MiddlewareInterface
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
        $sessionId = $request->getCookie('session_id');
        
        if (!$sessionId) {
            $this->logger->warning('인증 실패: 세션 ID 없음');
            return new Response('', 302, ['Location' => '/login']);
        }
        
        $isValid = true;
        
        if (!$isValid) {
            $this->logger->warning('인증 실패: 유효하지 않은 세션');
            return new Response('', 302, ['Location' => '/login']);
        }
        
        return $next($request);
    }
}
