<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Add environment variables loading
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use DI\ContainerBuilder;
use RocketSourcer\Core\Application;

// 컨테이너 생성
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../config/container.php');
$container = $containerBuilder->build();

// Register error handler
$errorHandler = $container->get(\RocketSourcer\Core\ErrorHandler::class);
$errorHandler->register();

// 애플리케이션 실행
$app = $container->get(Application::class);
$app->run();