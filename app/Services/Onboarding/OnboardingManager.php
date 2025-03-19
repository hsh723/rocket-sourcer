<?php

namespace App\Services\Onboarding;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OnboardingManager
{
    /**
     * 온보딩 투어 정의
     */
    protected $tours = [
        'dashboard' => [
            'title' => '대시보드 투어',
            'description' => '대시보드의 주요 기능을 소개합니다.',
            'steps' => [
                [
                    'title' => '대시보드에 오신 것을 환영합니다',
                    'content' => 'Rocket Sourcer 대시보드에 오신 것을 환영합니다. 이 투어에서는 주요 기능을 소개합니다.',
                    'position' => 'center',
                    'overlay' => true
                ],
                [
                    'title' => '요약 통계',
                    'content' => '이 섹션에서는 활성 프로젝트, 추적 중인 키워드, 소싱 기회 등의 주요 통계를 확인할 수 있습니다.',
                    'highlightSelector' => '.dashboard-stats',
                    'position' => 'auto'
                ],
                [
                    'title' => '최근 활동',
                    'content' => '최근에 수행한 작업과 업데이트를 확인할 수 있습니다.',
                    'highlightSelector' => '.recent-activities',
                    'position' => 'auto'
                ],
                [
                    'title' => '시장 트렌드',
                    'content' => '관심 카테고리의 최신 트렌드와 인사이트를 확인할 수 있습니다.',
                    'highlightSelector' => '.market-trends',
                    'position' => 'auto'
                ],
                [
                    'title' => '사이드바 메뉴',
                    'content' => '왼쪽 사이드바를 통해 다양한 기능에 접근할 수 있습니다.',
                    'highlightSelector' => '.sidebar-menu',
                    'position' => 'right'
                ],
                [
                    'title' => '투어 완료',
                    'content' => '대시보드 투어를 완료했습니다. 이제 Rocket Sourcer를 사용하여 제품을 소싱하고 시장 기회를 발견해 보세요!',
                    'position' => 'center',
                    'overlay' => true
                ]
            ]
        ],
        'project' => [
            'title' => '프로젝트 투어',
            'description' => '프로젝트 생성 및 관리 방법을 소개합니다.',
            'steps' => [
                [
                    'title' => '프로젝트 페이지에 오신 것을 환영합니다',
                    'content' => '이 페이지에서는 소싱 프로젝트를 생성하고 관리할 수 있습니다.',
                    'position' => 'center',
                    'overlay' => true
                ],
                [
                    'title' => '새 프로젝트 생성',
                    'content' => '이 버튼을 클릭하여 새 프로젝트를 생성할 수 있습니다.',
                    'highlightSelector' => '.create-project-btn',
                    'position' => 'auto'
                ],
                [
                    'title' => '프로젝트 목록',
                    'content' => '이 섹션에서는 모든 프로젝트를 확인하고 관리할 수 있습니다.',
                    'highlightSelector' => '.project-list',
                    'position' => 'auto'
                ],
                [
                    'title' => '프로젝트 필터',
                    'content' => '다양한 기준으로 프로젝트를 필터링할 수 있습니다.',
                    'highlightSelector' => '.project-filters',
                    'position' => 'auto'
                ],
                [
                    'title' => '투어 완료',
                    'content' => '프로젝트 투어를 완료했습니다. 이제 첫 번째 프로젝트를 생성해 보세요!',
                    'position' => 'center',
                    'overlay' => true
                ]
            ]
        ],
        'keyword' => [
            'title' => '키워드 분석 투어',
            'description' => '키워드 분석 도구 사용 방법을 소개합니다.',
            'steps' => [
                [
                    'title' => '키워드 분석 페이지에 오신 것을 환영합니다',
                    'content' => '이 페이지에서는 키워드를 분석하고 연구할 수 있습니다.',
                    'position' => 'center',
                    'overlay' => true
                ],
                [
                    'title' => '키워드 추가',
                    'content' => '이 섹션에서 키워드를 추가할 수 있습니다. 수동으로 입력하거나 CSV 파일을 업로드하거나 경쟁사 제품 URL에서 추출할 수 있습니다.',
                    'highlightSelector' => '.keyword-input',
                    'position' => 'auto'
                ],
                [
                    'title' => '키워드 분석 결과',
                    'content' => '이 섹션에서는 키워드 분석 결과를 확인할 수 있습니다. 검색 볼륨, 경쟁 수준, 계절성 등의 정보를 제공합니다.',
                    'highlightSelector' => '.keyword-results',
                    'position' => 'auto'
                ],
                [
                    'title' => '관련 키워드',
                    'content' => '이 섹션에서는 관련 키워드와 롱테일 키워드를 확인할 수 있습니다.',
                    'highlightSelector' => '.related-keywords',
                    'position' => 'auto'
                ],
                [
                    'title' => '투어 완료',
                    'content' => '키워드 분석 투어를 완료했습니다. 이제 키워드를 분석하고 시장 기회를 발견해 보세요!',
                    'position' => 'center',
                    'overlay' => true
                ]
            ]
        ]
    ];
    
    /**
     * 사용자의 온보딩 상태를 가져옵니다.
     *
     * @param User $user
     * @return array
     */
    public function getUserOnboardingStatus(User $user): array
    {
        $cacheKey = 'onboarding_status_' . $user->id;
        
        return Cache::remember($cacheKey, 60, function () use ($user) {
            $status = DB::table('user_onboarding')
                ->where('user_id', $user->id)
                ->first();
            
            if (!$status) {
                return [
                    'user_id' => $user->id,
                    'completed_tours' => [],
                    'current_tour' => null,
                    'progress' => 0,
                    'last_activity' => null
                ];
            }
            
            return [
                'user_id' => $user->id,
                'completed_tours' => json_decode($status->completed_tours, true) ?: [],
                'current_tour' => $status->current_tour,
                'progress' => $status->progress,
                'last_activity' => $status->last_activity
            ];
        });
    }
    
    /**
     * 사용자의 온보딩 상태를 업데이트합니다.
     *
     * @param User $user
     * @param array $data
     * @return bool
     */
    public function updateUserOnboardingStatus(User $user, array $data): bool
    {
        try {
            $existingStatus = DB::table('user_onboarding')
                ->where('user_id', $user->id)
                ->first();
            
            $updateData = [
                'last_activity' => Carbon::now()
            ];
            
            if (isset($data['completed_tours'])) {
                $updateData['completed_tours'] = json_encode($data['completed_tours']);
            }
            
            if (isset($data['current_tour'])) {
                $updateData['current_tour'] = $data['current_tour'];
            }
            
            if (isset($data['progress'])) {
                $updateData['progress'] = $data['progress'];
            }
            
            if ($existingStatus) {
                DB::table('user_onboarding')
                    ->where('user_id', $user->id)
                    ->update($updateData);
            } else {
                $updateData['user_id'] = $user->id;
                
                if (!isset($updateData['completed_tours'])) {
                    $updateData['completed_tours'] = json_encode([]);
                }
                
                if (!isset($updateData['progress'])) {
                    $updateData['progress'] = 0;
                }
                
                DB::table('user_onboarding')->insert($updateData);
            }
            
            // 캐시 삭제
            Cache::forget('onboarding_status_' . $user->id);
            
            return true;
        } catch (\Exception $e) {
            Log::error('사용자 온보딩 상태 업데이트 중 오류 발생: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 투어를 완료로 표시합니다.
     *
     * @param User $user
     * @param string $tourId
     * @return bool
     */
    public function markTourAsCompleted(User $user, string $tourId): bool
    {
        $status = $this->getUserOnboardingStatus($user);
        $completedTours = $status['completed_tours'];
        
        if (!in_array($tourId, $completedTours)) {
            $completedTours[] = $tourId;
        }
        
        return $this->updateUserOnboardingStatus($user, [
            'completed_tours' => $completedTours,
            'current_tour' => ($status['current_tour'] === $tourId) ? null : $status['current_tour']
        ]);
    }
    
    /**
     * 현재 투어를 설정합니다.
     *
     * @param User $user
     * @param string|null $tourId
     * @param int $progress
     * @return bool
     */
    public function setCurrentTour(User $user, ?string $tourId, int $progress = 0): bool
    {
        return $this->updateUserOnboardingStatus($user, [
            'current_tour' => $tourId,
            'progress' => $progress
        ]);
    }
    
    /**
     * 투어 진행 상황을 업데이트합니다.
     *
     * @param User $user
     * @param int $progress
     * @return bool
     */
    public function updateTourProgress(User $user, int $progress): bool
    {
        return $this->updateUserOnboardingStatus($user, [
            'progress' => $progress
        ]);
    }
    
    /**
     * 사용자가 투어를 완료했는지 확인합니다.
     *
     * @param User $user
     * @param string $tourId
     * @return bool
     */
    public function hasTourCompleted(User $user, string $tourId): bool
    {
        $status = $this->getUserOnboardingStatus($user);
        return in_array($tourId, $status['completed_tours']);
    }
    
    /**
     * 모든 투어 정의를 가져옵니다.
     *
     * @return array
     */
    public function getAllTours(): array
    {
        return $this->tours;
    }
    
    /**
     * 특정 투어 정의를 가져옵니다.
     *
     * @param string $tourId
     * @return array|null
     */
    public function getTour(string $tourId): ?array
    {
        return $this->tours[$tourId] ?? null;
    }
    
    /**
     * 사용자에게 추천할 다음 투어를 가져옵니다.
     *
     * @param User $user
     * @return array|null
     */
    public function getNextRecommendedTour(User $user): ?array
    {
        $status = $this->getUserOnboardingStatus($user);
        $completedTours = $status['completed_tours'];
        
        // 기본 투어 순서
        $tourOrder = ['dashboard', 'project', 'keyword'];
        
        foreach ($tourOrder as $tourId) {
            if (!in_array($tourId, $completedTours)) {
                $tour = $this->getTour($tourId);
                
                if ($tour) {
                    return [
                        'id' => $tourId,
                        'title' => $tour['title'],
                        'description' => $tour['description']
                    ];
                }
            }
        }
        
        return null;
    }
    
    /**
     * 사용자의 온보딩 진행 상황을 가져옵니다.
     *
     * @param User $user
     * @return array
     */
    public function getUserOnboardingProgress(User $user): array
    {
        $status = $this->getUserOnboardingStatus($user);
        $completedTours = $status['completed_tours'];
        $totalTours = count($this->tours);
        $completedCount = count($completedTours);
        
        $progress = [
            'completed_count' => $completedCount,
            'total_count' => $totalTours,
            'percentage' => ($totalTours > 0) ? round(($completedCount / $totalTours) * 100) : 0,
            'completed_tours' => []
        ];
        
        foreach ($completedTours as $tourId) {
            $tour = $this->getTour($tourId);
            
            if ($tour) {
                $progress['completed_tours'][] = [
                    'id' => $tourId,
                    'title' => $tour['title']
                ];
            }
        }
        
        $progress['next_tour'] = $this->getNextRecommendedTour($user);
        
        return $progress;
    }
} 