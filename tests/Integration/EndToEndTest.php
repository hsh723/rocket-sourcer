<?php

namespace Tests\Integration;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Services\Help\HelpContentManager;
use App\Services\Onboarding\OnboardingManager;

class EndToEndTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 사용자 온보딩 및 도움말 시스템 전체 흐름 테스트
     *
     * @return void
     */
    public function testUserOnboardingAndHelpFlow()
    {
        // 사용자 생성
        $user = User::factory()->create();
        $this->actingAs($user);

        // 1. 대시보드 접근 및 온보딩 시작
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('대시보드에 오신 것을 환영합니다');

        // 2. 온보딩 투어 시작
        $response = $this->get('/api/onboarding/tour/dashboard');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'title',
            'description',
            'steps',
            'completed'
        ]);

        // 3. 온보딩 투어 완료 표시
        $response = $this->post('/api/onboarding/complete/dashboard');
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // 4. 다음 추천 투어 확인
        $response = $this->get('/api/onboarding/next-tour');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'title',
            'description'
        ]);

        // 5. 도움말 시스템 접근
        $response = $this->get('/help');
        $response->assertStatus(200);

        // 6. 도움말 카테고리 확인
        $response = $this->get('/api/help/initial-data');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'categories',
            'popular_articles'
        ]);

        // 7. 도움말 검색
        $response = $this->get('/api/help/search?q=시작하기');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'query',
            'results'
        ]);

        // 8. 도움말 피드백 제출
        $response = $this->post('/api/help/feedback', [
            'category_slug' => 'getting-started',
            'article_slug' => 'introduction',
            'is_helpful' => true
        ]);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // 9. 온보딩 진행 상황 확인
        $response = $this->get('/api/onboarding/progress');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'completed_count',
            'total_count',
            'percentage',
            'completed_tours'
        ]);
    }

    /**
     * 관리자 도움말 콘텐츠 관리 테스트
     *
     * @return void
     */
    public function testAdminHelpContentManagement()
    {
        // 관리자 사용자 생성
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        // 1. 도움말 카테고리 생성
        $response = $this->post('/help/category/save', [
            'slug' => 'test-category',
            'name' => '테스트 카테고리',
            'description' => '테스트 카테고리 설명',
            'icon' => 'fa-book'
        ]);
        $response->assertStatus(302); // 리다이렉트
        $response->assertSessionHas('success');

        // 2. 도움말 항목 생성
        $response = $this->post('/help/save', [
            'category_slug' => 'test-category',
            'article_slug' => 'test-article',
            'content' => "# 테스트 항목\n\n테스트 항목 내용입니다."
        ]);
        $response->assertStatus(302); // 리다이렉트
        $response->assertSessionHas('success');

        // 3. 도움말 항목 확인
        $response = $this->get('/help/article/test-category/test-article');
        $response->assertStatus(200);
        $response->assertSee('테스트 항목');

        // 4. 도움말 항목 수정
        $response = $this->post('/help/save', [
            'category_slug' => 'test-category',
            'article_slug' => 'test-article',
            'content' => "# 수정된 테스트 항목\n\n수정된 테스트 항목 내용입니다."
        ]);
        $response->assertStatus(302); // 리다이렉트
        $response->assertSessionHas('success');

        // 5. 수정된 도움말 항목 확인
        $response = $this->get('/help/article/test-category/test-article');
        $response->assertStatus(200);
        $response->assertSee('수정된 테스트 항목');

        // 6. 도움말 항목 삭제
        $response = $this->post('/help/delete', [
            'category_slug' => 'test-category',
            'article_slug' => 'test-article'
        ]);
        $response->assertStatus(302); // 리다이렉트
        $response->assertSessionHas('success');

        // 7. 도움말 카테고리 삭제
        $response = $this->post('/help/category/delete', [
            'slug' => 'test-category'
        ]);
        $response->assertStatus(302); // 리다이렉트
        $response->assertSessionHas('success');
    }

    /**
     * 서비스 의존성 주입 테스트
     *
     * @return void
     */
    public function testServiceDependencyInjection()
    {
        // HelpContentManager 서비스 확인
        $helpManager = app(HelpContentManager::class);
        $this->assertInstanceOf(HelpContentManager::class, $helpManager);

        // OnboardingManager 서비스 확인
        $onboardingManager = app(OnboardingManager::class);
        $this->assertInstanceOf(OnboardingManager::class, $onboardingManager);
    }
} 