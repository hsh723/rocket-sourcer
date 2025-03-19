<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

/**
 * 사용자 활동 타임라인 서비스
 * 
 * 사용자의 활동을 기록하고 타임라인 형태로 제공합니다.
 * 활동 유형별 필터링, 기간별 조회, 통계 분석 등의 기능을 제공합니다.
 */
class UserActivityTimeline
{
    /**
     * 활동 유형 정의
     */
    const ACTIVITY_LOGIN = 'login';
    const ACTIVITY_LOGOUT = 'logout';
    const ACTIVITY_SEARCH = 'search';
    const ACTIVITY_VIEW_PRODUCT = 'view_product';
    const ACTIVITY_ADD_TO_FAVORITES = 'add_to_favorites';
    const ACTIVITY_REMOVE_FROM_FAVORITES = 'remove_from_favorites';
    const ACTIVITY_EXPORT_DATA = 'export_data';
    const ACTIVITY_CHANGE_SETTINGS = 'change_settings';
    const ACTIVITY_USE_RECOMMENDATION = 'use_recommendation';
    const ACTIVITY_PROVIDE_FEEDBACK = 'provide_feedback';
    
    /**
     * 활동 카테고리 정의
     */
    const CATEGORIES = [
        'authentication' => [self::ACTIVITY_LOGIN, self::ACTIVITY_LOGOUT],
        'product_interaction' => [self::ACTIVITY_VIEW_PRODUCT, self::ACTIVITY_ADD_TO_FAVORITES, self::ACTIVITY_REMOVE_FROM_FAVORITES],
        'search' => [self::ACTIVITY_SEARCH],
        'data_management' => [self::ACTIVITY_EXPORT_DATA],
        'settings' => [self::ACTIVITY_CHANGE_SETTINGS],
        'recommendation' => [self::ACTIVITY_USE_RECOMMENDATION, self::ACTIVITY_PROVIDE_FEEDBACK]
    ];
    
    /**
     * 활동 테이블 이름
     * 
     * @var string
     */
    protected $activityTable = 'user_activities';
    
    /**
     * 활동 기록
     * 
     * @param int $userId 사용자 ID
     * @param string $activityType 활동 유형
     * @param array $data 추가 데이터
     * @param string|null $ip IP 주소
     * @return bool 성공 여부
     */
    public function recordActivity(int $userId, string $activityType, array $data = [], ?string $ip = null)
    {
        try {
            // 활동 유형 검증
            if (!$this->isValidActivityType($activityType)) {
                Log::warning("유효하지 않은 활동 유형: {$activityType}", [
                    'user_id' => $userId,
                    'data' => $data
                ]);
                return false;
            }
            
            // IP 주소가 제공되지 않은 경우 현재 요청의 IP 사용
            if ($ip === null && request()) {
                $ip = request()->ip();
            }
            
            // 활동 기록
            $result = DB::table($this->activityTable)->insert([
                'user_id' => $userId,
                'activity_type' => $activityType,
                'data' => json_encode($data),
                'ip_address' => $ip,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            return $result;
        } catch (\Exception $e) {
            Log::error("활동 기록 오류: {$e->getMessage()}", [
                'user_id' => $userId,
                'activity_type' => $activityType,
                'data' => $data,
                'exception' => $e
            ]);
            
            return false;
        }
    }
    
    /**
     * 사용자의 활동 타임라인을 가져옵니다.
     * 
     * @param int $userId 사용자 ID
     * @param array $options 조회 옵션
     * @return Collection 활동 컬렉션
     */
    public function getUserTimeline(int $userId, array $options = [])
    {
        try {
            $query = DB::table($this->activityTable)
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc');
            
            // 활동 유형 필터링
            if (isset($options['activity_types']) && is_array($options['activity_types'])) {
                $query->whereIn('activity_type', $options['activity_types']);
            }
            
            // 카테고리 필터링
            if (isset($options['category'])) {
                $activityTypes = $this->getActivityTypesByCategory($options['category']);
                if (!empty($activityTypes)) {
                    $query->whereIn('activity_type', $activityTypes);
                }
            }
            
            // 기간 필터링
            if (isset($options['start_date'])) {
                $query->where('created_at', '>=', $options['start_date']);
            }
            
            if (isset($options['end_date'])) {
                $query->where('created_at', '<=', $options['end_date']);
            }
            
            // 페이지네이션
            $perPage = $options['per_page'] ?? 20;
            $page = $options['page'] ?? 1;
            
            $activities = $query->paginate($perPage, ['*'], 'page', $page);
            
            // 데이터 필드 JSON 디코딩
            $activities->getCollection()->transform(function ($activity) {
                $activity->data = json_decode($activity->data);
                return $activity;
            });
            
            return $activities;
        } catch (\Exception $e) {
            Log::error("활동 타임라인 조회 오류: {$e->getMessage()}", [
                'user_id' => $userId,
                'options' => $options,
                'exception' => $e
            ]);
            
            return collect();
        }
    }
    
    /**
     * 사용자의 최근 활동을 가져옵니다.
     * 
     * @param int $userId 사용자 ID
     * @param int $limit 조회 개수
     * @return Collection 활동 컬렉션
     */
    public function getRecentActivities(int $userId, int $limit = 10)
    {
        return $this->getUserTimeline($userId, [
            'per_page' => $limit,
            'page' => 1
        ]);
    }
    
    /**
     * 사용자의 활동 통계를 가져옵니다.
     * 
     * @param int $userId 사용자 ID
     * @param string|null $period 기간 (daily, weekly, monthly, yearly)
     * @return array 활동 통계
     */
    public function getActivityStats(int $userId, ?string $period = null)
    {
        try {
            $query = DB::table($this->activityTable)
                ->where('user_id', $userId);
            
            // 기간 필터링
            if ($period) {
                $startDate = $this->getStartDateByPeriod($period);
                if ($startDate) {
                    $query->where('created_at', '>=', $startDate);
                }
            }
            
            // 활동 유형별 카운트
            $activityCounts = $query
                ->select('activity_type', DB::raw('count(*) as count'))
                ->groupBy('activity_type')
                ->get()
                ->pluck('count', 'activity_type')
                ->toArray();
            
            // 카테고리별 통계
            $categoryStats = [];
            foreach (self::CATEGORIES as $category => $activityTypes) {
                $categoryStats[$category] = 0;
                foreach ($activityTypes as $type) {
                    $categoryStats[$category] += $activityCounts[$type] ?? 0;
                }
            }
            
            // 시간대별 활동 분포
            $hourlyDistribution = $query
                ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('count(*) as count'))
                ->groupBy(DB::raw('HOUR(created_at)'))
                ->get()
                ->pluck('count', 'hour')
                ->toArray();
            
            // 결과 포맷팅
            $stats = [
                'total_activities' => array_sum($activityCounts),
                'activity_counts' => $activityCounts,
                'category_stats' => $categoryStats,
                'hourly_distribution' => $hourlyDistribution,
                'period' => $period
            ];
            
            return $stats;
        } catch (\Exception $e) {
            Log::error("활동 통계 조회 오류: {$e->getMessage()}", [
                'user_id' => $userId,
                'period' => $period,
                'exception' => $e
            ]);
            
            return [
                'total_activities' => 0,
                'activity_counts' => [],
                'category_stats' => [],
                'hourly_distribution' => [],
                'period' => $period,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 사용자의 활동 트렌드를 가져옵니다.
     * 
     * @param int $userId 사용자 ID
     * @param string $period 기간 (daily, weekly, monthly)
     * @param int $duration 기간 수
     * @return array 활동 트렌드
     */
    public function getActivityTrend(int $userId, string $period = 'daily', int $duration = 30)
    {
        try {
            $query = DB::table($this->activityTable)
                ->where('user_id', $userId);
            
            // 기간 설정
            $endDate = now();
            $startDate = $this->getStartDateForTrend($period, $duration);
            
            $query->whereBetween('created_at', [$startDate, $endDate]);
            
            // 기간별 그룹화 포맷
            $groupFormat = $this->getGroupFormatByPeriod($period);
            
            // 기간별 활동 카운트
            $trend = $query
                ->select(DB::raw("DATE_FORMAT(created_at, '{$groupFormat}') as period"), DB::raw('count(*) as count'))
                ->groupBy(DB::raw("DATE_FORMAT(created_at, '{$groupFormat}')"))
                ->orderBy('period')
                ->get()
                ->pluck('count', 'period')
                ->toArray();
            
            // 빈 기간 채우기
            $filledTrend = $this->fillEmptyPeriods($trend, $period, $startDate, $endDate);
            
            return [
                'period_type' => $period,
                'duration' => $duration,
                'trend' => $filledTrend
            ];
        } catch (\Exception $e) {
            Log::error("활동 트렌드 조회 오류: {$e->getMessage()}", [
                'user_id' => $userId,
                'period' => $period,
                'duration' => $duration,
                'exception' => $e
            ]);
            
            return [
                'period_type' => $period,
                'duration' => $duration,
                'trend' => [],
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 활동 유형이 유효한지 확인합니다.
     * 
     * @param string $activityType 활동 유형
     * @return bool 유효 여부
     */
    protected function isValidActivityType(string $activityType)
    {
        $allActivityTypes = [];
        foreach (self::CATEGORIES as $activityTypes) {
            $allActivityTypes = array_merge($allActivityTypes, $activityTypes);
        }
        
        return in_array($activityType, $allActivityTypes);
    }
    
    /**
     * 카테고리에 속한 활동 유형을 가져옵니다.
     * 
     * @param string $category 카테고리
     * @return array 활동 유형 배열
     */
    protected function getActivityTypesByCategory(string $category)
    {
        return self::CATEGORIES[$category] ?? [];
    }
    
    /**
     * 기간에 따른 시작 날짜를 가져옵니다.
     * 
     * @param string $period 기간 (daily, weekly, monthly, yearly)
     * @return Carbon|null 시작 날짜
     */
    protected function getStartDateByPeriod(string $period)
    {
        $now = now();
        
        switch ($period) {
            case 'daily':
                return $now->copy()->startOfDay();
            case 'weekly':
                return $now->copy()->startOfWeek();
            case 'monthly':
                return $now->copy()->startOfMonth();
            case 'yearly':
                return $now->copy()->startOfYear();
            default:
                return null;
        }
    }
    
    /**
     * 트렌드 분석을 위한 시작 날짜를 가져옵니다.
     * 
     * @param string $period 기간 (daily, weekly, monthly)
     * @param int $duration 기간 수
     * @return Carbon 시작 날짜
     */
    protected function getStartDateForTrend(string $period, int $duration)
    {
        $now = now();
        
        switch ($period) {
            case 'daily':
                return $now->copy()->subDays($duration);
            case 'weekly':
                return $now->copy()->subWeeks($duration);
            case 'monthly':
                return $now->copy()->subMonths($duration);
            default:
                return $now->copy()->subDays($duration);
        }
    }
    
    /**
     * 기간별 그룹화 포맷을 가져옵니다.
     * 
     * @param string $period 기간 (daily, weekly, monthly)
     * @return string 그룹화 포맷
     */
    protected function getGroupFormatByPeriod(string $period)
    {
        switch ($period) {
            case 'daily':
                return '%Y-%m-%d';
            case 'weekly':
                return '%x-%v'; // ISO 연도 및 주차
            case 'monthly':
                return '%Y-%m';
            default:
                return '%Y-%m-%d';
        }
    }
    
    /**
     * 빈 기간을 0으로 채웁니다.
     * 
     * @param array $trend 트렌드 데이터
     * @param string $period 기간 (daily, weekly, monthly)
     * @param Carbon $startDate 시작 날짜
     * @param Carbon $endDate 종료 날짜
     * @return array 채워진 트렌드 데이터
     */
    protected function fillEmptyPeriods(array $trend, string $period, Carbon $startDate, Carbon $endDate)
    {
        $filledTrend = [];
        $current = $startDate->copy();
        
        while ($current <= $endDate) {
            $key = $this->formatDateByPeriod($current, $period);
            $filledTrend[$key] = $trend[$key] ?? 0;
            
            // 다음 기간으로 이동
            switch ($period) {
                case 'daily':
                    $current->addDay();
                    break;
                case 'weekly':
                    $current->addWeek();
                    break;
                case 'monthly':
                    $current->addMonth();
                    break;
                default:
                    $current->addDay();
            }
        }
        
        return $filledTrend;
    }
    
    /**
     * 날짜를 기간에 맞게 포맷팅합니다.
     * 
     * @param Carbon $date 날짜
     * @param string $period 기간 (daily, weekly, monthly)
     * @return string 포맷팅된 날짜
     */
    protected function formatDateByPeriod(Carbon $date, string $period)
    {
        switch ($period) {
            case 'daily':
                return $date->format('Y-m-d');
            case 'weekly':
                return $date->format('Y') . '-' . $date->format('W');
            case 'monthly':
                return $date->format('Y-m');
            default:
                return $date->format('Y-m-d');
        }
    }
} 