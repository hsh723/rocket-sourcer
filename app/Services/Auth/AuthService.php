<?php

namespace RocketSourcer\Services\Auth;

use RocketSourcer\Models\User;
use RocketSourcer\Repositories\UserRepository;
use Psr\Log\LoggerInterface;

class AuthService
{
    private JWTService $jwt;
    private Cache $cache;
    private Logger $logger;
    private ?User $user = null;

    public function __construct(JWTService $jwt, Cache $cache, Logger $logger)
    {
        $this->jwt = $jwt;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    public function attempt(string $email, string $password): ?array
    {
        try {
            $user = User::findByEmail($email);

            if (!$user || !$this->verifyPassword($password, $user->getPassword())) {
                $this->logger->info('Failed login attempt', ['email' => $email]);
                return null;
            }

            if (!$user->isActive()) {
                $this->logger->info('Inactive user login attempt', ['email' => $email]);
                return null;
            }

            $this->user = $user;
            return $this->createTokens($user);
        } catch (\Throwable $e) {
            $this->logger->error('Login error', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function logout(string $token): bool
    {
        try {
            $this->user = null;
            return $this->jwt->invalidate($token);
        } catch (\Throwable $e) {
            $this->logger->error('Logout error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function refresh(string $refreshToken): ?array
    {
        try {
            $tokens = $this->jwt->refresh($refreshToken);
            if (!$tokens) {
                return null;
            }

            $payload = $this->jwt->verify($tokens['access_token']);
            if (!$payload) {
                return null;
            }

            $this->user = User::find($payload['sub']);
            return $tokens;
        } catch (\Throwable $e) {
            $this->logger->error('Token refresh error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function authenticate(string $token): bool
    {
        try {
            $payload = $this->jwt->verify($token);
            if (!$payload) {
                return false;
            }

            $this->user = User::find($payload['sub']);
            return $this->user !== null;
        } catch (\Throwable $e) {
            $this->logger->error('Authentication error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function user(): ?User
    {
        return $this->user;
    }

    public function id(): ?int
    {
        return $this->user ? $this->user->getId() : null;
    }

    public function check(): bool
    {
        return $this->user !== null;
    }

    public function can(string $ability, ...$args): bool
    {
        if (!$this->check()) {
            return false;
        }

        return $this->user->can($ability, ...$args);
    }

    private function createTokens(User $user): array
    {
        $payload = [
            'sub' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles()
        ];

        return [
            'access_token' => $this->jwt->createAccessToken($payload),
            'refresh_token' => $this->jwt->createRefreshToken($payload),
            'expires_in' => $this->jwt->getAccessTokenTtl()
        ];
    }

    private function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public function getRateLimitKey(string $email): string
    {
        return "login_attempts:{$email}";
    }

    public function incrementLoginAttempts(string $email): int
    {
        $key = $this->getRateLimitKey($email);
        return $this->cache->increment($key, 1);
    }

    public function clearLoginAttempts(string $email): bool
    {
        $key = $this->getRateLimitKey($email);
        return $this->cache->delete($key);
    }

    public function hasTooManyLoginAttempts(string $email): bool
    {
        $key = $this->getRateLimitKey($email);
        $attempts = (int)$this->cache->get($key, 0);
        return $attempts >= 5; // 5회 시도 제한
    }

    public function getAttemptsLeft(string $email): int
    {
        $key = $this->getRateLimitKey($email);
        $attempts = (int)$this->cache->get($key, 0);
        return max(5 - $attempts, 0);
    }
}