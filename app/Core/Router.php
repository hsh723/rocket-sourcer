<?php

namespace RocketSourcer\Core;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use RocketSourcer\Core\Contracts\RouterInterface;
use RocketSourcer\Core\Contracts\RequestInterface;
use RocketSourcer\Core\Contracts\ResponseInterface;
use function FastRoute\simpleDispatcher;
use RocketSourcer\Core\Http\Request;
use RocketSourcer\Core\Http\Response;
use RocketSourcer\Core\Exceptions\HttpException;

class Router implements RouterInterface
{
    private array $routes = [];
    private array $middleware = [];
    private array $globalMiddleware = [];
    private ?Dispatcher $dispatcher = null;
    
    /**
     * HTTP GET 라우트 등록
     *
     * @param string $pattern 라우트 패턴
     * @param mixed $handler 핸들러
     * @return self
     */
    public function get(string $pattern, $handler): self
    {
        return $this->addRoute('GET', $pattern, $handler);
    }
    
    /**
     * HTTP POST 라우트 등록
     *
     * @param string $pattern 라우트 패턴
     * @param mixed $handler 핸들러
     * @return self
     */
    public function post(string $pattern, $handler): self
    {
        return $this->addRoute('POST', $pattern, $handler);
    }
    
    /**
     * HTTP PUT 라우트 등록
     *
     * @param string $pattern 라우트 패턴
     * @param mixed $handler 핸들러
     * @return self
     */
    public function put(string $pattern, $handler): self
    {
        return $this->addRoute('PUT', $pattern, $handler);
    }
    
    /**
     * HTTP DELETE 라우트 등록
     *
     * @param string $pattern 라우트 패턴
     * @param mixed $handler 핸들러
     * @return self
     */
    public function delete(string $pattern, $handler): self
    {
        return $this->addRoute('DELETE', $pattern, $handler);
    }
    
    /**
     * 미들웨어 등록
     *
     * @param string $pattern 라우트 패턴
     * @param callable $middleware 미들웨어 함수
     * @return self
     */
    public function middleware(string $pattern, callable $middleware): self
    {
        $this->middleware[$pattern][] = $middleware;
        return $this;
    }
    
    /**
     * 전역 미들웨어 등록
     *
     * @param callable $middleware 전역 미들웨어 함수
     * @return self
     */
    public function addGlobalMiddleware($middleware): self
    {
        $this->globalMiddleware[] = $middleware;
        return $this;
    }
    
    /**
     * 요청 처리
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws \Exception
     */
    public function dispatch(RequestInterface $request): ResponseInterface
    {
        $dispatcher = $this->getDispatcher();
        $routeInfo = $dispatcher->dispatch(
            $request->method(),
            $request->uri()
        );
        
        if ($routeInfo[0] === Dispatcher::FOUND) {
            $routeInfo[1] = $this->wrapWithMiddleware(
                $routeInfo[1],
                $request->uri()
            );
        }
        
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                throw new \Exception('Not Found', 404);
                
            case Dispatcher::METHOD_NOT_ALLOWED:
                throw new \Exception('Method Not Allowed', 405);
                
            case Dispatcher::FOUND:
                return $this->handleFound($routeInfo, $request);
        }
        
        throw new \Exception('Internal Server Error', 500);
    }
    
    /**
     * 라우트 등록
     *
     * @param string $method
     * @param string $pattern
     * @param mixed $handler
     * @return self
     */
    private function addRoute(string $method, string $pattern, $handler): self
    {
        $this->routes[] = ['method' => $method, 'pattern' => $pattern, 'handler' => $handler];
        $this->dispatcher = null;
        return $this;
    }
    
    /**
     * 매칭된 라우트 처리
     *
     * @param array $routeInfo
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    private function handleFound(array $routeInfo, RequestInterface $request): ResponseInterface
    {
        [$handler, $vars] = [$routeInfo[1], $routeInfo[2]];
        
        // 미들웨어 실행
        $handler = $this->wrapWithMiddleware($handler, $request->uri());
        
        // 핸들러 실행
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $controller = new $class();
            return $controller->$method($request, $vars);
        }
        
        return $handler($request, $vars);
    }
    
    private function getDispatcher(): Dispatcher
    {
} 