<?php

namespace RocketSourcer\Api;

use RocketSourcer\Core\Http\Request;
use RocketSourcer\Core\Http\Response;
use RocketSourcer\Services\Auth\AuthService;
use RocketSourcer\Core\Logger;

abstract class BaseController
{
    protected AuthService $auth;
    protected Logger $logger;

    public function __construct()
    {
        $this->auth = app(AuthService::class);
        $this->logger = app(Logger::class);
    }

    protected function response($data = null, int $status = 200, array $headers = []): Response
    {
        return Response::json([
            'success' => $status >= 200 && $status < 300,
            'data' => $data
        ], $status, $headers);
    }

    protected function error(string $message, int $status = 400, array $errors = []): Response
    {
        return Response::json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $status);
    }

    protected function validateRequest(Request $request, array $rules): ?Response
    {
        $errors = $request->validate($rules);
        
        if (!empty($errors)) {
            return $this->error('Validation failed', 422, $errors);
        }

        return null;
    }

    protected function authorize(string $ability, ...$args): bool
    {
        return $this->auth->can($ability, ...$args);
    }

    protected function authorizeOrFail(string $ability, ...$args): void
    {
        if (!$this->authorize($ability, ...$args)) {
            throw new \Exception('Unauthorized', 403);
        }
    }

    protected function user()
    {
        return $this->auth->user();
    }

    protected function userId(): ?int
    {
        return $this->auth->id();
    }

    protected function logInfo(string $message, array $context = []): void
    {
        $this->logger->info($message, array_merge([
            'user_id' => $this->userId(),
            'ip' => request()->ip()
        ], $context));
    }

    protected function logError(string $message, array $context = []): void
    {
        $this->logger->error($message, array_merge([
            'user_id' => $this->userId(),
            'ip' => request()->ip()
        ], $context));
    }
} 