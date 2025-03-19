<?php

namespace RocketSourcer\Services\Coupang;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use RocketSourcer\Core\Cache;
use RocketSourcer\Services\Coupang\Response\ApiResponse;

class CoupangApiService
{
    protected Client $client;
    protected CoupangAuthService $auth;
    protected Cache $cache;
    protected LoggerInterface $logger;
    protected array $config;
    protected array $rateLimits = [];

    public function __construct(
        array $config,
        CoupangAuthService $auth,
        Cache $cache,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->auth = $auth;
        $this->cache = $cache;
        $this->logger = $logger;

        $this->client = new Client([
            'base_uri' => $config['api']['base_url'],
            'timeout' => $config['request']['timeout'],
            'connect_timeout' => $config['request']['connect_timeout'],
        ]);
    }

    protected function request(
        string $method,
        string $path,
        array $options = [],
        bool $useCache = true
    ): ApiResponse {
        if (!$this->auth->isConfigured()) {
            return ApiResponse::error('401', '인증 정보가 설정되지 않았습니다.');
        }

        if (!$this->checkRateLimit()) {
            return ApiResponse::error('429', '요청 제한을 초과했습니다.');
        }

        $cacheKey = $this->getCacheKey($method, $path, $options);
        
        if ($useCache && $this->config['cache']['enabled']) {
            if ($cached = $this->cache->get($cacheKey)) {
                $this->logger->debug('캐시된 응답 사용', [
                    'method' => $method,
                    'path' => $path,
                ]);
                return $cached;
            }
        }

        try {
            $response = $this->executeRequest($method, $path, $options);

            if ($useCache && $this->config['cache']['enabled']) {
                $this->cache->set(
                    $cacheKey,
                    $response,
                    $this->config['cache']['ttl']
                );
            }

            return $response;
        } catch (GuzzleException $e) {
            $this->logger->error('API 요청 실패', [
                'method' => $method,
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return $this->handleRequestError($e);
        }
    }

    protected function executeRequest(string $method, string $path, array $options = []): ApiResponse
    {
        $headers = $this->auth->generateAuthHeaders(
            $method,
            $path,
            $options['query'] ?? []
        );

        $options['headers'] = array_merge(
            $options['headers'] ?? [],
            $headers,
            [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]
        );

        $attempt = 1;
        $maxAttempts = $this->config['request']['retry']['max_attempts'];
        $delay = $this->config['request']['retry']['delay'];
        $multiplier = $this->config['request']['retry']['multiplier'];

        do {
            try {
                $response = $this->client->request($method, $path, $options);
                $contents = $response->getBody()->getContents();
                $data = json_decode($contents, true);

                if (!$this->auth->validateResponse($response->getHeaders())) {
                    throw new \Exception('응답 서명이 유효하지 않습니다.');
                }

                $this->updateRateLimit($response->getHeaders());

                return ApiResponse::success(
                    $data['data'] ?? $data,
                    $data['message'] ?? null,
                    $data
                );
            } catch (\Exception $e) {
                $this->logger->warning('API 요청 재시도', [
                    'attempt' => $attempt,
                    'max_attempts' => $maxAttempts,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt === $maxAttempts) {
                    throw $e;
                }

                usleep($delay * 1000);
                $delay *= $multiplier;
                $attempt++;
            }
        } while ($attempt <= $maxAttempts);

        throw new \Exception('최대 재시도 횟수를 초과했습니다.');
    }

    protected function handleRequestError(GuzzleException $e): ApiResponse
    {
        $response = $e->getResponse();
        
        if (!$response) {
            return ApiResponse::error('500', '네트워크 오류가 발생했습니다.');
        }

        $contents = $response->getBody()->getContents();
        $data = json_decode($contents, true);

        return ApiResponse::error(
            (string)$response->getStatusCode(),
            $data['message'] ?? '알 수 없는 오류가 발생했습니다.',
            $data
        );
    }

    protected function getCacheKey(string $method, string $path, array $options = []): string
    {
        $key = "coupang_api:{$method}:{$path}";
        
        if (!empty($options)) {
            $key .= ':' . md5(json_encode($options));
        }

        return $key;
    }

    protected function checkRateLimit(): bool
    {
        if (!$this->config['rate_limit']['enabled']) {
            return true;
        }

        $now = time();
        $window = $this->config['rate_limit']['window'];
        $maxRequests = $this->config['rate_limit']['max_requests'];

        // 만료된 요청 제거
        $this->rateLimits = array_filter(
            $this->rateLimits,
            fn($timestamp) => $timestamp > $now - $window
        );

        if (count($this->rateLimits) >= $maxRequests) {
            return false;
        }

        $this->rateLimits[] = $now;
        return true;
    }

    protected function updateRateLimit(array $headers): void
    {
        if (isset($headers['X-RateLimit-Remaining'])) {
            $this->logger->debug('Rate limit 업데이트', [
                'remaining' => $headers['X-RateLimit-Remaining'],
                'reset' => $headers['X-RateLimit-Reset'] ?? 'unknown',
            ]);
        }
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function clearCache(): void
    {
        if ($this->config['cache']['enabled']) {
            $this->cache->clear($this->config['cache']['prefix'] . '*');
        }
    }
} 