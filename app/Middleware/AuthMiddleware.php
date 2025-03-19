<?php

namespace RocketSourcer\Middleware;

use RocketSourcer\Core\Http\Request;
use RocketSourcer\Core\Http\Response;
use RocketSourcer\Services\Auth\AuthService;

class AuthMiddleware
{
    private AuthService $auth;
    private array $except;

    public function __construct(AuthService $auth, array $except = [])
    {
        $this->auth = $auth;
        $this->except = $except;
    }

    public function handle(Request $request, callable $next)
    {
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        $token = $this->getBearerToken($request);
        if (!$token) {
            return Response::json([
                'success' => false,
                'message' => 'No token provided'
            ], 401);
        }

        if (!$this->auth->authenticate($token)) {
            return Response::json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        return $next($request);
    }

    private function shouldSkip(Request $request): bool
    {
        $path = $request->getPathInfo();
        
        foreach ($this->except as $pattern) {
            if ($pattern === $path) {
                return true;
            }

            $pattern = preg_quote($pattern, '#');
            $pattern = str_replace('\*', '.*', $pattern);
            
            if (preg_match('#^' . $pattern . '$#', $path)) {
                return true;
            }
        }

        return false;
    }

    private function getBearerToken(Request $request): ?string
    {
        $header = $request->header('Authorization');
        if (!$header || !preg_match('/Bearer\s+(.+)/', $header, $matches)) {
            return null;
        }

        return $matches[1];
    }
} 