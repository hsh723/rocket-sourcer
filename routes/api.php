<?php

use FastRoute\RouteCollector;
use RocketSourcer\Api\Controllers\KeywordController;
use RocketSourcer\Api\Controllers\ProductController;
use RocketSourcer\Api\Controllers\MarginController;
use RocketSourcer\Api\AuthController;
use RocketSourcer\Middleware\AuthMiddleware;
use RocketSourcer\Core\Routing\Router;

/** @var \RocketSourcer\Core\Router $router */

return function (RouteCollector $r) {
    // 키워드 분석 API
    $r->addGroup('/api/keywords', function (RouteCollector $r) {
        $r->addRoute('GET', '', [KeywordController::class, 'index']);
        $r->addRoute('POST', '/analyze', [KeywordController::class, 'analyze']);
        $r->addRoute('GET', '/{id:\d+}', [KeywordController::class, 'show']);
    });

    // 제품 분석 API
    $r->addGroup('/api/products', function (RouteCollector $r) {
        $r->addRoute('GET', '', [ProductController::class, 'index']);
        $r->addRoute('POST', '/analyze', [ProductController::class, 'analyze']);
        $r->addRoute('GET', '/{id:\d+}', [ProductController::class, 'show']);
    });

    // 마진 계산 API
    $r->addGroup('/api/margins', function (RouteCollector $r) {
        $r->addRoute('POST', '/calculate', [MarginController::class, 'calculate']);
        $r->addRoute('GET', '/history', [MarginController::class, 'history']);
    });

    // 인증이 필요하지 않은 라우트
    $router->post('/api/auth/login', [AuthController::class, 'login']);
    $router->post('/api/auth/refresh', [AuthController::class, 'refresh']);

    // 인증이 필요한 라우트
    $router->middleware('/api', new AuthMiddleware($jwtService, [
        '/api/auth/login',
        '/api/auth/refresh'
    ]));

    $router->post('/api/auth/logout', [AuthController::class, 'logout']);
    $router->get('/api/auth/me', [AuthController::class, 'me']);
};

/** @var Router $router */

// Dashboard routes
$router->get('/dashboard/summary', 'DashboardController@getSummary');
$router->get('/dashboard/trends', 'DashboardController@getTrends');
$router->get('/dashboard/recent-keywords', 'DashboardController@getRecentKeywords');
$router->get('/dashboard/recent-products', 'DashboardController@getRecentProducts');
$router->get('/dashboard/notifications', 'DashboardController@getNotifications');
$router->delete('/dashboard/notifications/{id}', 'DashboardController@dismissNotification');

// Keyword routes
$router->group(['prefix' => 'keywords'], function ($router) {
    $router->get('/', [KeywordController::class, 'index']);
    $router->post('/', [KeywordController::class, 'store']);
    $router->get('/{id}', [KeywordController::class, 'show']);
    $router->delete('/{id}', [KeywordController::class, 'destroy']);
    $router->post('/analyze', [KeywordController::class, 'analyze']);
    $router->get('/trends', [KeywordController::class, 'trends']);
    $router->get('/recommendations', [KeywordController::class, 'recommendations']);
});

// Product routes
$router->get('/products', 'ProductController@index');
$router->get('/products/{id}', 'ProductController@show');
$router->post('/products/analyze', 'ProductController@analyze');
$router->get('/products/{id}/history', 'ProductController@history');
$router->get('/products/{id}/competitors', 'ProductController@competitors');
$router->get('/products/{id}/trends', 'ProductController@trends');

/*
|--------------------------------------------------------------------------
| 제품 관련 라우트
|--------------------------------------------------------------------------
*/
$router->prefix('products')->group(function ($router) {
    $router->get('/', 'ProductController@index');
    $router->post('/', 'ProductController@store');
    $router->get('/{id}', 'ProductController@show');
    $router->put('/{id}', 'ProductController@update');
    $router->delete('/{id}', 'ProductController@destroy');
    $router->post('/{id}/analyze', 'ProductController@analyze');
    $router->get('/{id}/profit', 'ProductController@calculateProfit');
    $router->get('/{id}/competitors', 'ProductController@competitors');
    $router->get('/{id}/trends', 'ProductController@trends');
    $router->get('/recommendations', 'ProductController@recommendations');
});

// 도움말 API 라우트
$router->prefix('help')->name('help.')->group(function () {
    $router->get('/initial-data', 'Api\HelpController@getInitialData');
    $router->get('/search', 'Api\HelpController@search');
    $router->get('/category/{categorySlug}', 'Api\HelpController@getCategory');
    $router->get('/article/{categorySlug}/{articleSlug}', 'Api\HelpController@getArticle');
    $router->post('/feedback', 'Api\HelpController@submitFeedback')->middleware('auth');
});

// 온보딩 API 라우트
$router->prefix('onboarding')->name('onboarding.')->middleware('auth')->group(function () {
    $router->get('/status', 'OnboardingController@getStatus');
    $router->get('/progress', 'OnboardingController@getProgress');
    $router->get('/tours', 'OnboardingController@getAllTours');
    $router->get('/tour/{tourId}', 'OnboardingController@getTour');
    $router->get('/next-tour', 'OnboardingController@getNextRecommendedTour');
    $router->post('/complete/{tourId}', 'OnboardingController@completeTour');
    $router->post('/set-current', 'OnboardingController@setCurrentTour');
    $router->post('/update-progress', 'OnboardingController@updateProgress');
}); 