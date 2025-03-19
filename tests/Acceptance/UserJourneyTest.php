<?php

namespace Tests\Acceptance;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class UserJourneyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 신규 사용자 온보딩 여정 테스트
     *
     * @return void
     */
    public function testNewUserOnboardingJourney()
    {
        // 1. 사용자 등록
        $response = $this->post('/register', [
            'name' => '테스트 사용자',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password'
        ]);
        
        $response->assertStatus(302); // 리다이렉트
        $response->assertRedirect('/dashboard'); // 대시보드로 리다이렉트
        
        // 2. 대시보드 접근
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('대시보드에 오신 것을 환영합니다');
        
        // 3. 온보딩 상태 확인
        $response = $this->get('/api/onboarding/status');
        $response->assertStatus(200);
        $response->assertJson([
            'current_tour' => null,
            'progress' => 0
        ]);
        
        // 4. 첫 번째 투어 시작
        $response = $this->get('/api/onboarding/next-tour');
        $response->assertStatus(200);
        $tourId = $response->json('id');
        
        $response = $this->post('/api/onboarding/set-current', [
            'tour_id' => $tourId,
            'progress' => 0
        ]);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        // 5. 투어 완료
        $response = $this->post('/api/onboarding/complete/' . $tourId);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        // 6. 온보딩 진행 상황 확인
        $response = $this->get('/api/onboarding/progress');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'completed_count',
            'total_count',
            'percentage',
            'completed_tours'
        ]);
        
        // 7. 도움말 센터 방문
        $response = $this->get('/help');
        $response->assertStatus(200);
        
        // 8. 도움말 검색
        $response = $this->get('/api/help/search?q=시작하기');
        $response->assertStatus(200);
        
        // 9. 도움말 항목 읽기
        $response = $this->get('/api/help/initial-data');
        $response->assertStatus(200);
        
        $categories = $response->json('categories');
        if (!empty($categories)) {
            $categorySlug = $categories[0]['slug'];
            
            $response = $this->get('/api/help/category/' . $categorySlug);
            $response->assertStatus(200);
            
            $articles = $response->json('articles');
            if (!empty($articles)) {
                $articleSlug = $articles[0]['slug'];
                
                $response = $this->get('/api/help/article/' . $categorySlug . '/' . $articleSlug);
                $response->assertStatus(200);
                
                // 10. 도움말 피드백 제출
                $response = $this->post('/api/help/feedback', [
                    'category_slug' => $categorySlug,
                    'article_slug' => $articleSlug,
                    'is_helpful' => true
                ]);
                $response->assertStatus(200);
                $response->assertJson(['success' => true]);
            }
        }
    }
    
    /**
     * 기존 사용자 재방문 여정 테스트
     *
     * @return void
     */
    public function testReturningUserJourney()
    {
        // 1. 사용자 생성 및 온보딩 상태 설정
        $user = User::factory()->create();
        $this->actingAs($user);
        
        // 온보딩 상태 설정 (대시보드 투어 완료)
        $this->post('/api/onboarding/complete/dashboard');
        
        // 2. 로그아웃
        $this->post('/logout');
        
        // 3. 다시 로그인
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password'
        ]);
        
        $response->assertStatus(302); // 리다이렉트
        $response->assertRedirect('/dashboard'); // 대시보드로 리다이렉트
        
        // 4. 온보딩 상태 확인
        $response = $this->get('/api/onboarding/status');
        $response->assertStatus(200);
        $response->assertJson([
            'completed_tours' => ['dashboard']
        ]);
        
        // 5. 다음 추천 투어 확인
        $response = $this->get('/api/onboarding/next-tour');
        $response->assertStatus(200);
        $tourId = $response->json('id');
        $this->assertNotEquals('dashboard', $tourId);
        
        // 6. 두 번째 투어 시작 및 완료
        $response = $this->post('/api/onboarding/set-current', [
            'tour_id' => $tourId,
            'progress' => 0
        ]);
        $response->assertStatus(200);
        
        $response = $this->post('/api/onboarding/complete/' . $tourId);
        $response->assertStatus(200);
        
        // 7. 온보딩 진행 상황 확인
        $response = $this->get('/api/onboarding/progress');
        $response->assertStatus(200);
        $this->assertTrue($response->json('completed_count') >= 2);
    }
    
    /**
     * 관리자 콘텐츠 관리 여정 테스트
     *
     * @return void
     */
    public function testAdminContentManagementJourney()
    {
        // 1. 관리자 사용자 생성
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        
        // 2. 도움말 카테고리 생성
        $categorySlug = 'test-journey-category';
        $response = $this->post('/help/category/save', [
            'slug' => $categorySlug,
            'name' => '테스트 여정 카테고리',
            'description' => '사용자 여정 테스트를 위한 카테고리',
            'icon' => 'fa-book'
        ]);
        
        $response->assertStatus(302); // 리다이렉트
        
        // 3. 도움말 항목 생성
        $articleSlug = 'test-journey-article';
        $response = $this->post('/help/save', [
            'category_slug' => $categorySlug,
            'article_slug' => $articleSlug,
            'content' => "# 테스트 여정 항목\n\n사용자 여정 테스트를 위한 도움말 항목입니다."
        ]);
        
        $response->assertStatus(302); // 리다이렉트
        
        // 4. 생성된 도움말 항목 확인
        $response = $this->get('/help/article/' . $categorySlug . '/' . $articleSlug);
        $response->assertStatus(200);
        $response->assertSee('테스트 여정 항목');
        
        // 5. 도움말 항목 수정
        $response = $this->post('/help/save', [
            'category_slug' => $categorySlug,
            'article_slug' => $articleSlug,
            'content' => "# 수정된 테스트 여정 항목\n\n사용자 여정 테스트를 위한 수정된 도움말 항목입니다."
        ]);
        
        $response->assertStatus(302); // 리다이렉트
        
        // 6. 수정된 도움말 항목 확인
        $response = $this->get('/help/article/' . $categorySlug . '/' . $articleSlug);
        $response->assertStatus(200);
        $response->assertSee('수정된 테스트 여정 항목');
        
        // 7. 도움말 항목 삭제
        $response = $this->post('/help/delete', [
            'category_slug' => $categorySlug,
            'article_slug' => $articleSlug
        ]);
        
        $response->assertStatus(302); // 리다이렉트
        
        // 8. 도움말 카테고리 삭제
        $response = $this->post('/help/category/delete', [
            'slug' => $categorySlug
        ]);
        
        $response->assertStatus(302); // 리다이렉트
        
        // 9. 삭제된 도움말 항목 확인
        $response = $this->get('/help/article/' . $categorySlug . '/' . $articleSlug);
        $response->assertStatus(404);
    }
    
    /**
     * 모바일 사용자 여정 테스트
     *
     * @return void
     */
    public function testMobileUserJourney()
    {
        // 모바일 사용자 에이전트 설정
        $mobileUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1';
        
        // 1. 사용자 생성
        $user = User::factory()->create();
        
        // 2. 모바일 기기로 로그인
        $response = $this->withHeaders([
            'User-Agent' => $mobileUserAgent
        ])->post('/login', [
            'email' => $user->email,
            'password' => 'password'
        ]);
        
        $response->assertStatus(302); // 리다이렉트
        
        // 3. 모바일 대시보드 접근
        $response = $this->withHeaders([
            'User-Agent' => $mobileUserAgent
        ])->get('/dashboard');
        
        $response->assertStatus(200);
        
        // 4. 모바일 도움말 센터 접근
        $response = $this->withHeaders([
            'User-Agent' => $mobileUserAgent
        ])->get('/help');
        
        $response->assertStatus(200);
        
        // 5. 모바일 온보딩 투어 접근
        $response = $this->withHeaders([
            'User-Agent' => $mobileUserAgent
        ])->get('/api/onboarding/tour/dashboard');
        
        $response->assertStatus(200);
    }
} 