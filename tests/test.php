<?php
// 간단한 기능 테스트 스크립트

require_once __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;
use RocketSourcer\Core\Config;
use RocketSourcer\Services\Analysis\CrossCategoryAnalysisService;
use RocketSourcer\Services\Cache\CacheInterface;
use Psr\Log\LoggerInterface;

// 테스트 시작 메시지
echo "로켓소서 기능 테스트를 시작합니다...\n";

try {
    $containerBuilder = new ContainerBuilder();
    $containerBuilder->addDefinitions(__DIR__ . '/../config/container.php');
    $container = $containerBuilder->build();
    
    echo "컨테이너 초기화 성공\n";
    
    $config = $container->get(Config::class);
    echo "설정 로드 성공: " . $config->get('app.name') . "\n";
    
    $logger = $container->get(LoggerInterface::class);
    $logger->info('테스트 로그 메시지');
    echo "로그 기록 성공\n";
    
    $cache = $container->get(CacheInterface::class);
    $testKey = 'test_key_' . time();
    $testValue = ['test' => true, 'timestamp' => time()];
    
    $cache->set($testKey, $testValue, 60);
    $retrieved = $cache->get($testKey);
    
    if ($retrieved && $retrieved['test'] === true) {
        echo "캐시 테스트 성공\n";
    } else {
        echo "캐시 테스트 실패\n";
    }
    
    $analysisService = $container->get(CrossCategoryAnalysisService::class);
    $result = $analysisService->analyzeProductCategories('test-product-id');
    
    if (isset($result['productId'])) {
        echo "크로스 카테고리 분석 테스트 성공\n";
    } else {
        echo "크로스 카테고리 분석 테스트 실패\n";
    }
    
    echo "모든 테스트가 완료되었습니다.\n";
    
} catch (Exception $e) {
    echo "테스트 중 오류 발생: " . $e->getMessage() . "\n";
    echo "위치: " . $e->getFile() . ':' . $e->getLine() . "\n";
    echo "스택 트레이스:\n" . $e->getTraceAsString() . "\n";
}
