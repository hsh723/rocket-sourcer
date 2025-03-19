<?php

namespace RocketSourcer\Services\Coupang;

use Psr\Log\LoggerInterface;
use RocketSourcer\Core\Cache;

class CoupangAuthService
{
    private string $accessKey;
    private string $secretKey;
    private Cache $cache;
    private LoggerInterface $logger;

    public function __construct(
        string $accessKey,
        string $secretKey,
        Cache $cache,
        LoggerInterface $logger
    ) {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    public function generateAuthHeaders(string $method, string $path, array $parameters = []): array
    {
        $timestamp = $this->getTimestamp();
        $signature = $this->generateSignature($method, $path, $timestamp, $parameters);

        return [
            'Authorization' => $this->getAuthorizationHeader($signature),
            'X-Timestamp' => $timestamp,
        ];
    }

    protected function generateSignature(
        string $method,
        string $path,
        string $timestamp,
        array $parameters = []
    ): string {
        $cacheKey = $this->getSignatureCacheKey($method, $path, $timestamp, $parameters);

        if ($cachedSignature = $this->cache->get($cacheKey)) {
            $this->logger->debug('Using cached signature', [
                'method' => $method,
                'path' => $path,
                'timestamp' => $timestamp,
            ]);
            return $cachedSignature;
        }

        $message = $this->buildSignatureMessage($method, $path, $timestamp, $parameters);
        $signature = $this->hmacSha256($message, $this->secretKey);

        $this->cache->set($cacheKey, $signature, 60); // 1분 캐시

        $this->logger->debug('Generated new signature', [
            'method' => $method,
            'path' => $path,
            'timestamp' => $timestamp,
        ]);

        return $signature;
    }

    protected function buildSignatureMessage(
        string $method,
        string $path,
        string $timestamp,
        array $parameters = []
    ): string {
        $parts = [
            strtoupper($method),
            $path,
            $timestamp,
        ];

        if (!empty($parameters)) {
            ksort($parameters);
            $queryString = http_build_query($parameters);
            $parts[] = $queryString;
        }

        return implode("\n", $parts);
    }

    protected function hmacSha256(string $message, string $secret): string
    {
        return hash_hmac('sha256', $message, $secret);
    }

    protected function getAuthorizationHeader(string $signature): string
    {
        return "CEA algorithm=HmacSHA256, access-key={$this->accessKey}, signed-date={$signature}";
    }

    protected function getTimestamp(): string
    {
        return gmdate('Y-m-d\TH:i:s\Z');
    }

    protected function getSignatureCacheKey(
        string $method,
        string $path,
        string $timestamp,
        array $parameters = []
    ): string {
        $key = "coupang_signature:{$method}:{$path}:{$timestamp}";
        
        if (!empty($parameters)) {
            $key .= ':' . md5(json_encode($parameters));
        }

        return $key;
    }

    public function validateResponse(array $headers): bool
    {
        if (!isset($headers['X-Coupang-Response-Signature'])) {
            return false;
        }

        // 실제 응답 서명 검증 로직 구현
        // 현재는 단순히 서명 존재 여부만 확인
        return true;
    }

    public function getAccessKey(): string
    {
        return $this->accessKey;
    }

    public function isConfigured(): bool
    {
        return !empty($this->accessKey) && !empty($this->secretKey);
    }
} 