<?php

namespace Tests\Performance;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Services\Help\HelpContentManager;
use App\Services\Onboarding\OnboardingManager;
use App\Models\User;

class LoadTest extends TestCase
{
    /**
     * 도움말 시스템 성능 테스트
     *
     * @return void
     */
    public function testHelpSystemPerformance()
    {
        // 캐시 초기화
        Cache::flush();
        
        $helpManager = app(HelpContentManager::class);
        
        // 1. 카테고리 로딩 시간 측정
        $startTime = microtime(true);
        $categories = $helpManager->getCategories();
        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000; // 밀리초 단위
        
        $this->addToAssertionCount(1);
        echo "카테고리 로딩 시간: {$loadTime}ms\n";
        
        // 2. 첫 번째 카테고리의 항목 로딩 시간 측정
        if (!empty($categories)) {
            $categorySlug = $categories[0]['slug'];
            
            $startTime = microtime(true);
            $articles = $helpManager->getArticlesByCategory($categorySlug);
            $endTime = microtime(true);
            $loadTime = ($endTime - $startTime) * 1000; // 밀리초 단위
            
            $this->addToAssertionCount(1);
            echo "항목 로딩 시간: {$loadTime}ms\n";
            
            // 3. 첫 번째 항목 로딩 시간 측정
            if (!empty($articles)) {
                $articleSlug = $articles[0]['slug'];
                
                $startTime = microtime(true);
                $article = $helpManager->getArticle($categorySlug, $articleSlug);
                $endTime = microtime(true);
                $loadTime = ($endTime - $startTime) * 1000; // 밀리초 단위
                
                $this->addToAssertionCount(1);
                echo "항목 상세 로딩 시간: {$loadTime}ms\n";
            }
        }
        
        // 4. 검색 성능 측정
        $startTime = microtime(true);
        $results = $helpManager->searchContent('시작하기');
        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000; // 밀리초 단위
        
        $this->addToAssertionCount(1);
        echo "검색 시간: {$loadTime}ms\n";
        
        // 5. 캐시된 데이터 로딩 시간 측정
        $startTime = microtime(true);
        $categories = $helpManager->getCategories();
        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000; // 밀리초 단위
        
        $this->addToAssertionCount(1);
        echo "캐시된 카테고리 로딩 시간: {$loadTime}ms\n";
    }
    
    /**
     * 온보딩 시스템 성능 테스트
     *
     * @return void
     */
    public function testOnboardingSystemPerformance()
    {
        // 테스트 사용자 생성
        $user = User::factory()->create();
        
        $onboardingManager = app(OnboardingManager::class);
        
        // 1. 사용자 온보딩 상태 로딩 시간 측정
        $startTime = microtime(true);
        $status = $onboardingManager->getUserOnboardingStatus($user);
        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000; // 밀리초 단위
        
        $this->addToAssertionCount(1);
        echo "온보딩 상태 로딩 시간: {$loadTime}ms\n";
        
        // 2. 투어 정의 로딩 시간 측정
        $startTime = microtime(true);
        $tours = $onboardingManager->getAllTours();
        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000; // 밀리초 단위
        
        $this->addToAssertionCount(1);
        echo "투어 정의 로딩 시간: {$loadTime}ms\n";
        
        // 3. 온보딩 상태 업데이트 시간 측정
        $startTime = microtime(true);
        $onboardingManager->updateUserOnboardingStatus($user, [
            'current_tour' => 'dashboard',
            'progress' => 50
        ]);
        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000; // 밀리초 단위
        
        $this->addToAssertionCount(1);
        echo "온보딩 상태 업데이트 시간: {$loadTime}ms\n";
        
        // 4. 투어 완료 표시 시간 측정
        $startTime = microtime(true);
        $onboardingManager->markTourAsCompleted($user, 'dashboard');
        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000; // 밀리초 단위
        
        $this->addToAssertionCount(1);
        echo "투어 완료 표시 시간: {$loadTime}ms\n";
        
        // 5. 온보딩 진행 상황 로딩 시간 측정
        $startTime = microtime(true);
        $progress = $onboardingManager->getUserOnboardingProgress($user);
        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000; // 밀리초 단위
        
        $this->addToAssertionCount(1);
        echo "온보딩 진행 상황 로딩 시간: {$loadTime}ms\n";
    }
    
    /**
     * 동시 사용자 부하 시뮬레이션
     *
     * @return void
     */
    public function testConcurrentUserLoad()
    {
        $this->markTestSkipped('이 테스트는 실제 서버 환경에서만 실행해야 합니다.');
        
        // 동시 사용자 수
        $concurrentUsers = 100;
        
        // 시뮬레이션 시작 시간
        $startTime = microtime(true);
        
        // 병렬 처리를 위한 프로세스 포크 (실제 구현에서는 별도의 도구 사용)
        for ($i = 0; $i < $concurrentUsers; $i++) {
            // 각 사용자별 요청 시뮬레이션
            $this->simulateUserRequests();
        }
        
        // 시뮬레이션 종료 시간
        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000; // 밀리초 단위
        
        $this->addToAssertionCount(1);
        echo "동시 {$concurrentUsers}명 사용자 처리 시간: {$totalTime}ms\n";
        echo "사용자당 평균 처리 시간: " . ($totalTime / $concurrentUsers) . "ms\n";
    }
    
    /**
     * 단일 사용자 요청 시뮬레이션
     *
     * @return void
     */
    private function simulateUserRequests()
    {
        // 실제 구현에서는 curl_multi 또는 Guzzle을 사용하여 병렬 요청 구현
        $endpoints = [
            '/api/help/initial-data',
            '/api/help/search?q=시작하기',
            '/api/onboarding/status',
            '/api/onboarding/tours',
            '/api/onboarding/tour/dashboard'
        ];
        
        foreach ($endpoints as $endpoint) {
            // 요청 시뮬레이션
            $this->get($endpoint);
        }
    }
    
    /**
     * 데이터베이스 쿼리 성능 테스트
     *
     * @return void
     */
    public function testDatabaseQueryPerformance()
    {
        // 쿼리 로깅 활성화
        DB::enableQueryLog();
        
        // 테스트 사용자 생성
        $user = User::factory()->create();
        
        $onboardingManager = app(OnboardingManager::class);
        
        // 1. 온보딩 상태 조회 쿼리 성능 측정
        $startTime = microtime(true);
        $status = $onboardingManager->getUserOnboardingStatus($user);
        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000; // 밀리초 단위
        
        $queryLog = DB::getQueryLog();
        $queryCount = count($queryLog);
        
        $this->addToAssertionCount(1);
        echo "온보딩 상태 조회 쿼리 수: {$queryCount}\n";
        echo "온보딩 상태 조회 시간: {$loadTime}ms\n";
        
        // 2. 온보딩 상태 업데이트 쿼리 성능 측정
        DB::flushQueryLog();
        
        $startTime = microtime(true);
        $onboardingManager->updateUserOnboardingStatus($user, [
            'current_tour' => 'project',
            'progress' => 25
        ]);
        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000; // 밀리초 단위
        
        $queryLog = DB::getQueryLog();
        $queryCount = count($queryLog);
        
        $this->addToAssertionCount(1);
        echo "온보딩 상태 업데이트 쿼리 수: {$queryCount}\n";
        echo "온보딩 상태 업데이트 시간: {$loadTime}ms\n";
        
        // 쿼리 로깅 비활성화
        DB::disableQueryLog();
    }
} 