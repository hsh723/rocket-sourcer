<?php

namespace RocketSourcer\Services\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use RocketSourcer\Core\Cache;
use RuntimeException;

class JWTService
{
    private string $key;
    private string $algorithm;
    private int $accessTokenTtl;
    private int $refreshTokenTtl;
    private Cache $cache;

    public function __construct(array $config, Cache $cache)
    {
        $this->key = $config['key'] ?? env('JWT_SECRET');
        $this->algorithm = $config['algorithm'] ?? 'HS256';
        $this->accessTokenTtl = $config['access_ttl'] ?? 3600;        // 1시간
        $this->refreshTokenTtl = $config['refresh_ttl'] ?? 2592000;   // 30일
        $this->cache = $cache;

        if (empty($this->key)) {
            throw new RuntimeException('JWT secret key is not configured');
        }
    }

    public function createAccessToken(array $payload): string
    {
        $payload['type'] = 'access';
        $payload['iat'] = time();
        $payload['exp'] = time() + $this->accessTokenTtl;
        $payload['jti'] = $this->generateTokenId();

        return JWT::encode($payload, $this->key, $this->algorithm);
    }

    public function createRefreshToken(array $payload): string
    {
        $payload['type'] = 'refresh';
        $payload['iat'] = time();
        $payload['exp'] = time() + $this->refreshTokenTtl;
        $payload['jti'] = $this->generateTokenId();

        $token = JWT::encode($payload, $this->key, $this->algorithm);
        $this->cache->set("refresh_token:{$payload['jti']}", true, $this->refreshTokenTtl);

        return $token;
    }

    public function verify(string $token): ?array
    {
        try {
            $payload = (array)JWT::decode($token, new Key($this->key, $this->algorithm));

            if ($this->isTokenBlacklisted($payload['jti'])) {
                return null;
            }

            if ($payload['type'] === 'refresh' && !$this->isRefreshTokenValid($payload['jti'])) {
                return null;
            }

            return $payload;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function invalidate(string $token): bool
    {
        try {
            $payload = (array)JWT::decode($token, new Key($this->key, $this->algorithm));
            return $this->blacklistToken($payload['jti'], $payload['exp'] - time());
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function refresh(string $refreshToken): ?array
    {
        $payload = $this->verify($refreshToken);

        if (!$payload || $payload['type'] !== 'refresh') {
            return null;
        }

        // 리프레시 토큰 재사용 방지를 위해 이전 토큰 무효화
        $this->invalidateRefreshToken($payload['jti']);

        $newPayload = [
            'sub' => $payload['sub'],
            'roles' => $payload['roles'] ?? []
        ];

        return [
            'access_token' => $this->createAccessToken($newPayload),
            'refresh_token' => $this->createRefreshToken($newPayload)
        ];
    }

    private function generateTokenId(): string
    {
        return bin2hex(random_bytes(16));
    }

    private function blacklistToken(string $jti, int $ttl): bool
    {
        return $this->cache->set("blacklist:{$jti}", true, $ttl);
    }

    private function isTokenBlacklisted(string $jti): bool
    {
        return $this->cache->has("blacklist:{$jti}");
    }

    private function isRefreshTokenValid(string $jti): bool
    {
        return $this->cache->has("refresh_token:{$jti}");
    }

    private function invalidateRefreshToken(string $jti): bool
    {
        return $this->cache->delete("refresh_token:{$jti}");
    }

    public function getAccessTokenTtl(): int
    {
        return $this->accessTokenTtl;
    }

    public function getRefreshTokenTtl(): int
    {
        return $this->refreshTokenTtl;
    }
} 