<?php
namespace RocketSourcer\Core\Routing;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use RocketSourcer\Core\Http\Request;
use function FastRoute\simpleDispatcher;

class Router
{
    private array $routes = [];

    public function get(string $path, $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute(string $method, string $path, $handler): void
    {
        $this->routes[] = ['method' => $method, 'path' => $path, 'handler' => $handler];
    }

    public function dispatch(Request $request): array
    {
        $dispatcher = simpleDispatcher(function(RouteCollector $r) {
            foreach ($this->routes as $route) {
                $r->addRoute($route['method'], $route['path'], $route['handler']);
            }
        });

        return $dispatcher->dispatch(
            $request->getMethod(),
            $request->getPath()
        );
    }

    public function registerDefaultRoutes(): void
    {
        // 홈페이지 라우트
        $this->get('/', 'HomeController@index');
        
        // 대시보드 경로
        $this->get('/dashboard', 'DashboardController@index');
        
        // 크로스 카테고리 경로
        $this->get('/cross-category', 'CrossCategoryController@index');
        
        // API 엔드포인트 추가
        $this->get('/api/analysis/cross-category', 'AnalysisController@analyzeCrossCategory');
        
        // 인증 관련 경로
        $this->get('/login', 'AuthController@loginPage');
        $this->post('/login', 'AuthController@login');
    }
}
