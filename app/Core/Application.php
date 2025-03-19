<?php

namespace RocketSourcer\Core;

use DI\Container;
use FastRoute\Dispatcher;
use Psr\Log\LoggerInterface;
use RocketSourcer\Core\Routing\Router;
use RocketSourcer\Core\Http\Request;
use RocketSourcer\Core\Http\Response;
use RocketSourcer\Core\Exceptions\HttpException;

class Application
{
    private Container $container;
    private Router $router;
    private LoggerInterface $logger;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->router = $container->get(Router::class);
        $this->logger = $container->get(LoggerInterface::class);
    }

    public function run(): void
    {
        try {
            $request = Request::createFromGlobals();
            $response = $this->handle($request);
            $this->send($response);
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    private function handle(Request $request): Response
    {
        $routeInfo = $this->router->dispatch($request);

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                throw new HttpException('Not Found', 404);
            case Dispatcher::METHOD_NOT_ALLOWED:
                throw new HttpException('Method Not Allowed', 405);
            case Dispatcher::FOUND:
                [$handler, $vars] = $this->resolveRoute($routeInfo);
                return $handler($request, ...$vars);
        }
    }

    private function resolveRoute(array $routeInfo): array
    {
        [, $handler, $vars] = $routeInfo;

        if (is_array($handler)) {
            [$controller, $method] = $handler;
            if (is_string($controller)) {
                $controller = $this->container->get($controller);
            }
            $handler = [$controller, $method];
        } elseif (is_string($handler)) {
            [$controller, $method] = explode('@', $handler);
            $controller = $this->container->get("RocketSourcer\\Api\\{$controller}");
            $handler = [$controller, $method];
        }

        return [$handler, $vars];
    }

    protected function send(Response $response): void
    {
        $headers = $response->getHeaders();
        if (is_array($headers)) {
            foreach ($headers as $name => $value) {
                header("$name: $value");
            }
        }
        
        http_response_code($response->getStatusCode());
        echo $response->getBody();
    }

    private function handleException(\Throwable $e): void
    {
        $this->logger->error($e->getMessage(), [
            'exception' => $e,
            'trace' => $e->getTraceAsString(),
        ]);

        $statusCode = $e instanceof HttpException ? $e->getCode() : 500;
        $response = new Response(
            json_encode([
                'error' => [
                    'message' => $e->getMessage(),
                    'code' => $statusCode,
                ]
            ]),
            $statusCode,
            ['Content-Type' => 'application/json']
        );

        $this->send($response);
    }

    /**
     * 애플리케이션 부트스트랩 후 실행되는 최적화 함수
     */
    private function optimize(): void
    {
        // 운영 환경에서 opcode 캐싱 활성화 확인
        if (php_sapi_name() !== 'cli' && !$this->isDebugMode()) {
            if (!extension_loaded('opcache')) {
                $this->logger->warning('운영 환경에서 OPcache가 활성화되지 않았습니다. 성능 최적화를 위해 OPcache 활성화를 권장합니다.');
            } elseif (!ini_get('opcache.enable')) {
                $this->logger->warning('OPcache가 설치되었지만 활성화되지 않았습니다. php.ini에서 opcache.enable=1로 설정하세요.');
            }
        }

        // PHP 메모리 및 실행 설정
        ini_set('memory_limit', '256M');
        ini_set('max_execution_time', '60');
        ini_set('upload_max_filesize', '10M');
        ini_set('post_max_size', '10M');
        
        // 디버그 모드에 따른 오류 표시 설정
        ini_set('display_errors', $this->isDebugMode() ? '1' : '0');
        
        // 세션 설정
        ini_set('session.cookie_httponly', '1');
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            ini_set('session.cookie_secure', '1');
        }
    }

    /**
     * 디버그 모드 확인
     */
    private function isDebugMode(): bool
    {
        return $this->container->get(\RocketSourcer\Core\Config::class)->get('app.debug', false);
    }
}