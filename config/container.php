<?php

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use FastRoute\RouteCollector;
use FastRoute\Dispatcher;
use RocketSourcer\Core\Application;
use RocketSourcer\Core\Routing\Router;
use RocketSourcer\Core\Http\Request;
use RocketSourcer\Core\Http\Response;
use RocketSourcer\Services\Crawler\CoupangCrawlerService;
use RocketSourcer\Services\Analysis\CrossCategoryAnalysisService;

return [
    // 로거 설정
    LoggerInterface::class => function(ContainerInterface $c) {
        $logger = new Logger('app');
        $today = date('Y-m-d');
        $logFile = __DIR__ . "/../logs/app-{$today}.log";
        $logLevel = $_ENV['APP_DEBUG'] === 'true' ? Logger::DEBUG : Logger::INFO;
        
        $fileHandler = new StreamHandler($logFile, $logLevel);
        $fileHandler->setFormatter(new LineFormatter(
            "[%datetime%] %level_name%: %message% %context% %extra%\n",
            "Y-m-d H:i:s",
            true,
            true
        ));
        
        $logger->pushHandler($fileHandler);
        
        if ($_ENV['APP_ENV'] === 'development') {
            $consoleHandler = new StreamHandler('php://stdout', $logLevel);
            $consoleHandler->setFormatter(new LineFormatter(
                "[%datetime%] %level_name%: %message% %context% %extra%\n",
                "Y-m-d H:i:s",
                true,
                true
            ));
            $logger->pushHandler($consoleHandler);
        }
        
        return $logger;
    },
    
    // 코어 클래스
    Application::class => function(ContainerInterface $c) {
        return new Application($c);
    },
    
    Router::class => function(ContainerInterface $c) {
        $router = new Router(
            new RouteCollector(
                new \FastRoute\RouteParser\Std(),
                new \FastRoute\DataGenerator\GroupCountBased()
            )
        );
        $router->registerDefaultRoutes();
        return $router;
    },
    
    Request::class => function(ContainerInterface $c) {
        return new Request();
    },
    
    Response::class => function(ContainerInterface $c) {
        return new Response();
    },
    
    // 서비스 클래스
    CoupangCrawlerService::class => function(ContainerInterface $c) {
        return new CoupangCrawlerService();
    },
    
    CrossCategoryAnalysisService::class => function(ContainerInterface $c) {
        return new CrossCategoryAnalysisService(
            $c->get(CoupangCrawlerService::class)
        );
    },

    // API 컨트롤러
    'AnalysisController' => function(ContainerInterface $c) {
        return new \RocketSourcer\Api\AnalysisController(
            $c->get(CrossCategoryAnalysisService::class),
            $c->get(LoggerInterface::class)
        );
    },

    // 대시보드 컨트롤러
    'DashboardController' => function(ContainerInterface $c) {
        return new \RocketSourcer\Api\DashboardController(
            $c->get(LoggerInterface::class)
        );
    },

    // 홈 컨트롤러
    'HomeController' => function(ContainerInterface $c) {
        return new \RocketSourcer\Api\HomeController(
            $c->get(LoggerInterface::class)
        );
    },

    // 데이터베이스 연결
    PDO::class => function(ContainerInterface $c) {
        // ...existing code from the prompt...
    },

    // 사용자 저장소
    \RocketSourcer\Repositories\UserRepository::class => function(ContainerInterface $c) {
        // ...existing code from the prompt...
    },

    // 인증 서비스
    \RocketSourcer\Services\Auth\AuthService::class => function(ContainerInterface $c) {
        // ...existing code from the prompt...
    },

    // 인증 컨트롤러
    'AuthController' => function(ContainerInterface $c) {
        // ...existing code from the prompt...
    },

    // 미들웨어
    \RocketSourcer\Core\Middleware\AuthMiddleware::class => function(ContainerInterface $c) {
        return new \RocketSourcer\Core\Middleware\AuthMiddleware(
            $c->get(\RocketSourcer\Services\Auth\AuthService::class),
            $c->get(LoggerInterface::class)
        );
    },

    \RocketSourcer\Core\Middleware\ApiKeyMiddleware::class => function(ContainerInterface $c) {
        return new \RocketSourcer\Core\Middleware\ApiKeyMiddleware(
            $c->get(\RocketSourcer\Services\Auth\AuthService::class),
            $c->get(LoggerInterface::class)
        );
    },

    \RocketSourcer\Core\Middleware\MiddlewarePipeline::class => function(ContainerInterface $c) {
        return new \RocketSourcer\Core\Middleware\MiddlewarePipeline();
    },

    \RocketSourcer\Core\ErrorHandler::class => function(ContainerInterface $c) {
        $debug = $_ENV['APP_DEBUG'] === 'true';
        return new \RocketSourcer\Core\ErrorHandler(
            $c->get(LoggerInterface::class),
            $debug
        );
    },

    // 설정 관리
    \RocketSourcer\Core\Config::class => function(ContainerInterface $c) {
        $config = [
            'app' => [
                'name' => $_ENV['APP_NAME'] ?? 'RocketSourcer',
                'env' => $_ENV['APP_ENV'] ?? 'production',
                'debug' => $_ENV['APP_DEBUG'] === 'true',
                'url' => $_ENV['APP_URL'] ?? 'http://localhost:8000'
            ],
            'database' => [
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'dbname' => $_ENV['DB_NAME'] ?? 'rocket_sourcer',
                'username' => $_ENV['DB_USER'] ?? 'root',
                'password' => $_ENV['DB_PASS'] ?? ''
            ],
            'coupang' => [
                'access_key' => $_ENV['COUPANG_ACCESS_KEY'] ?? '',
                'secret_key' => $_ENV['COUPANG_SECRET_KEY'] ?? ''
            ],
            'cache' => [
                'driver' => $_ENV['CACHE_DRIVER'] ?? 'file',
                'ttl' => (int)($_ENV['CACHE_TTL'] ?? 3600)
            ]
        ];
        
        return new \RocketSourcer\Core\Config($config);
    },

    // 캐시 서비스
    \RocketSourcer\Services\Cache\CacheInterface::class => function(ContainerInterface $c) {
        $config = $c->get(\RocketSourcer\Core\Config::class);
        $cacheDriver = $config->get('cache.driver', 'file');
        
        if ($cacheDriver === 'file') {
            $cacheDir = __DIR__ . '/../cache/';
            $cacheTtl = $config->get('cache.ttl', 3600);
            
            return new \RocketSourcer\Services\Cache\FileCache(
                $cacheDir,
                $cacheTtl,
                $c->get(LoggerInterface::class)
            );
        }
        
        throw new \InvalidArgumentException("지원하지 않는 캐시 드라이버: {$cacheDriver}");
    }
];