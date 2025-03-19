<?php

namespace App\Services\Monitoring;

use App\Models\Product;
use App\Models\User;
use App\Services\Monitoring\AlertService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProductPerformanceMonitor
{
    protected AlertService $alertService;
    
    // 성과 지표 임계값 설정
    protected array $thresholds = [
        'sales_decrease_percent' => 15,       // 판매량 감소 임계값 (%)
        'revenue_decrease_percent' => 20,     // 매출 감소 임계값 (%)
        'margin_decrease_percent' => 10,      // 마진 감소 임계값 (%)
        'view_decrease_percent' => 30,        // 조회수 감소 임계값 (%)
        'conversion_decrease_percent' => 25,  // 전환율 감소 임계값 (%)
        'min_review_rating' => 3.5,           // 최소 리뷰 평점
        'inventory_low_percent' => 20,        // 재고 부족 임계값 (%)
        'inventory_high_percent' => 200       // 재고 과잉 임계값 (%)
    ];
    
    /**
     * 모니터 생성자
     */
    public function __construct(AlertService $alertService)
    {
        $this->alertService = $alertService;
    }
    
    /**
     * 모든 제품의 성과 모니터링
     */
    public function monitorAllProducts(int $userId = null): array
    {
        try {
            $startTime = microtime(true);
            Log::info('모든 제품 성과 모니터링 시작');
            
            // 모니터링할 제품 가져오기
            $products = $this->getProductsToMonitor($userId);
            
            $alerts = [];
            $processedCount = 0;
            
            foreach ($products as $product) {
                // 제품별 성과 모니터링
                $productAlerts = $this->monitorProductPerformance($product);
                
                if (!empty($productAlerts)) {
                    $alerts = array_merge($alerts, $productAlerts);
                }
                
                $processedCount++;
                
                // 100개 제품마다 로그 기록
                if ($processedCount % 100 === 0) {
                    Log::info("제품 {$processedCount}/{$products->count()} 처리 완료");
                }
            }
            
            $duration = round(microtime(true) - $startTime, 2);
            Log::info("모든 제품 성과 모니터링 완료. 처리 시간: {$duration}초, 처리된 제품: {$processedCount}, 생성된 알림: " . count($alerts));
            
            return [
                'processed_count' => $processedCount,
                'alerts_count' => count($alerts),
                'alerts' => $alerts,
                'duration' => $duration
            ];
        } catch (\Exception $e) {
            Log::error('제품 성과 모니터링 중 오류 발생: ' . $e->getMessage(), [
                'user_id' => $userId,
                'exception' => $e
            ]);
            
            return [
                'processed_count' => 0,
                'alerts_count' => 0,
                'alerts' => [],
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 단일 제품의 성과 모니터링
     */
    public function monitorProductPerformance(Product $product): array
    {
        try {
            $alerts = [];
            
            // 판매 성과 모니터링
            $salesAlerts = $this->monitorSalesPerformance($product);
            if (!empty($salesAlerts)) {
                $alerts = array_merge($alerts, $salesAlerts);
            }
            
            // 리뷰 모니터링
            $reviewAlerts = $this->monitorReviews($product);
            if (!empty($reviewAlerts)) {
                $alerts = array_merge($alerts, $reviewAlerts);
            }
            
            // 재고 모니터링
            $inventoryAlerts = $this->monitorInventory($product);
            if (!empty($inventoryAlerts)) {
                $alerts = array_merge($alerts, $inventoryAlerts);
            }
            
            // 가격 변동 모니터링
            $priceAlerts = $this->monitorPriceChanges($product);
            if (!empty($priceAlerts)) {
                $alerts = array_merge($alerts, $priceAlerts);
            }
            
            return $alerts;
        } catch (\Exception $e) {
            Log::error('제품 성과 모니터링 중 오류 발생: ' . $e->getMessage(), [
                'product_id' => $product->id,
                'exception' => $e
            ]);
            
            return [];
        }
    }
    
    /**
     * 판매 성과 모니터링
     */
    protected function monitorSalesPerformance(Product $product): array
    {
        $alerts = [];
        
        // 현재 기간 데이터 (최근 7일)
        $currentPeriodStart = Carbon::now()->subDays(7);
        $currentPeriodEnd = Carbon::now();
        $currentPeriodData = $this->getPerformanceData($product, $currentPeriodStart, $currentPeriodEnd);
        
        // 이전 기간 데이터 (그 이전 7일)
        $previousPeriodStart = Carbon::now()->subDays(14);
        $previousPeriodEnd = Carbon::now()->subDays(7);
        $previousPeriodData = $this->getPerformanceData($product, $previousPeriodStart, $previousPeriodEnd);
        
        // 판매량 감소 확인
        if ($previousPeriodData['sales_count'] > 0) {
            $salesChangePercent = (($currentPeriodData['sales_count'] - $previousPeriodData['sales_count']) / $previousPeriodData['sales_count']) * 100;
            
            if ($salesChangePercent <= -$this->thresholds['sales_decrease_percent']) {
                $alerts[] = [
                    'type' => 'sales_decrease',
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'message' => "판매량이 {$this->thresholds['sales_decrease_percent']}% 이상 감소했습니다. (변화율: " . round($salesChangePercent, 2) . "%)",
                    'current_value' => $currentPeriodData['sales_count'],
                    'previous_value' => $previousPeriodData['sales_count'],
                    'change_percent' => round($salesChangePercent, 2),
                    'threshold' => $this->thresholds['sales_decrease_percent'],
                    'created_at' => Carbon::now()
                ];
            }
        }
        
        // 매출 감소 확인
        if ($previousPeriodData['revenue'] > 0) {
            $revenueChangePercent = (($currentPeriodData['revenue'] - $previousPeriodData['revenue']) / $previousPeriodData['revenue']) * 100;
            
            if ($revenueChangePercent <= -$this->thresholds['revenue_decrease_percent']) {
                $alerts[] = [
                    'type' => 'revenue_decrease',
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'message' => "매출이 {$this->thresholds['revenue_decrease_percent']}% 이상 감소했습니다. (변화율: " . round($revenueChangePercent, 2) . "%)",
                    'current_value' => $currentPeriodData['revenue'],
                    'previous_value' => $previousPeriodData['revenue'],
                    'change_percent' => round($revenueChangePercent, 2),
                    'threshold' => $this->thresholds['revenue_decrease_percent'],
                    'created_at' => Carbon::now()
                ];
            }
        }
        
        // 마진 감소 확인
        if ($previousPeriodData['margin_rate'] > 0) {
            $marginChangePercent = $currentPeriodData['margin_rate'] - $previousPeriodData['margin_rate'];
            
            if ($marginChangePercent <= -$this->thresholds['margin_decrease_percent']) {
                $alerts[] = [
                    'type' => 'margin_decrease',
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'message' => "마진율이 {$this->thresholds['margin_decrease_percent']}% 이상 감소했습니다. (변화: " . round($marginChangePercent, 2) . "%)",
                    'current_value' => $currentPeriodData['margin_rate'],
                    'previous_value' => $previousPeriodData['margin_rate'],
                    'change_percent' => round($marginChangePercent, 2),
                    'threshold' => $this->thresholds['margin_decrease_percent'],
                    'created_at' => Carbon::now()
                ];
            }
        }
        
        // 조회수 감소 확인
        if ($previousPeriodData['view_count'] > 0) {
            $viewChangePercent = (($currentPeriodData['view_count'] - $previousPeriodData['view_count']) / $previousPeriodData['view_count']) * 100;
            
            if ($viewChangePercent <= -$this->thresholds['view_decrease_percent']) {
                $alerts[] = [
                    'type' => 'view_decrease',
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'message' => "조회수가 {$this->thresholds['view_decrease_percent']}% 이상 감소했습니다. (변화율: " . round($viewChangePercent, 2) . "%)",
                    'current_value' => $currentPeriodData['view_count'],
                    'previous_value' => $previousPeriodData['view_count'],
                    'change_percent' => round($viewChangePercent, 2),
                    'threshold' => $this->thresholds['view_decrease_percent'],
                    'created_at' => Carbon::now()
                ];
            }
        }
        
        // 전환율 감소 확인
        if ($previousPeriodData['conversion_rate'] > 0) {
            $conversionChangePercent = (($currentPeriodData['conversion_rate'] - $previousPeriodData['conversion_rate']) / $previousPeriodData['conversion_rate']) * 100;
            
            if ($conversionChangePercent <= -$this->thresholds['conversion_decrease_percent']) {
                $alerts[] = [
                    'type' => 'conversion_decrease',
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'message' => "전환율이 {$this->thresholds['conversion_decrease_percent']}% 이상 감소했습니다. (변화율: " . round($conversionChangePercent, 2) . "%)",
                    'current_value' => $currentPeriodData['conversion_rate'],
                    'previous_value' => $previousPeriodData['conversion_rate'],
                    'change_percent' => round($conversionChangePercent, 2),
                    'threshold' => $this->thresholds['conversion_decrease_percent'],
                    'created_at' => Carbon::now()
                ];
            }
        }
        
        return $alerts;
    }
    
    /**
     * 리뷰 모니터링
     */
    protected function monitorReviews(Product $product): array
    {
        $alerts = [];
        
        // 최근 리뷰 데이터 가져오기
        $recentReviews = $this->getRecentReviews($product);
        
        // 평균 평점 계산
        $averageRating = $recentReviews->avg('rating') ?? 0;
        
        // 낮은 평점 확인
        if ($averageRating < $this->thresholds['min_review_rating'] && $recentReviews->count() >= 5) {
            $alerts[] = [
                'type' => 'low_rating',
                'product_id' => $product->id,
                'product_name' => $product->name,
                'message' => "최근 리뷰 평점이 기준치보다 낮습니다. (평점: " . round($averageRating, 1) . ")",
                'current_value' => round($averageRating, 1),
                'threshold' => $this->thresholds['min_review_rating'],
                'review_count' => $recentReviews->count(),
                'created_at' => Carbon::now()
            ];
        }
        
        // 부정적인 리뷰 키워드 확인
        $negativeKeywords = $this->detectNegativeKeywords($recentReviews);
        if (!empty($negativeKeywords)) {
            $alerts[] = [
                'type' => 'negative_reviews',
                'product_id' => $product->id,
                'product_name' => $product->name,
                'message' => "최근 리뷰에서 부정적인 키워드가 발견되었습니다: " . implode(', ', array_keys($negativeKeywords)),
                'keywords' => $negativeKeywords,
                'review_count' => $recentReviews->count(),
                'created_at' => Carbon::now()
            ];
        }
        
        return $alerts;
    }
    
    /**
     * 재고 모니터링
     */
    protected function monitorInventory(Product $product): array
    {
        $alerts = [];
        
        // 재고 데이터 가져오기
        $inventory = $product->inventory ?? 0;
        $averageSalesPerDay = $this->getAverageSalesPerDay($product);
        
        // 예상 소진 일수
        $daysRemaining = $averageSalesPerDay > 0 ? $inventory / $averageSalesPerDay : 30;
        
        // 재고 부족 확인
        if ($daysRemaining < 7) {
            $alerts[] = [
                'type' => 'low_inventory',
                'product_id' => $product->id,
                'product_name' => $product->name,
                'message' => "재고가 7일 이내에 소진될 예정입니다. (남은 일수: " . round($daysRemaining, 1) . "일)",
                'current_inventory' => $inventory,
                'average_sales_per_day' => $averageSalesPerDay,
                'days_remaining' => round($daysRemaining, 1),
                'created_at' => Carbon::now()
            ];
        }
        
        // 재고 과잉 확인
        if ($daysRemaining > 60 && $inventory > 0 && $averageSalesPerDay > 0) {
            $alerts[] = [
                'type' => 'high_inventory',
                'product_id' => $product->id,
                'product_name' => $product->name,
                'message' => "재고가 60일 이상 남아있습니다. 과잉 재고 상태입니다. (남은 일수: " . round($daysRemaining, 1) . "일)",
                'current_inventory' => $inventory,
                'average_sales_per_day' => $averageSalesPerDay,
                'days_remaining' => round($daysRemaining, 1),
                'created_at' => Carbon::now()
            ];
        }
        
        return $alerts;
    }
    
    /**
     * 가격 변동 모니터링
     */
    protected function monitorPriceChanges(Product $product): array
    {
        $alerts = [];
        
        // 현재 가격
        $currentPrice = $product->price ?? 0;
        
        // 이전 가격 이력 가져오기
        $priceHistory = $this->getPriceHistory($product);
        
        if (empty($priceHistory) || $currentPrice === 0) {
            return $alerts;
        }
        
        // 최근 가격 변동 확인
        $lastPriceChange = end($priceHistory);
        $priceChangePercent = (($currentPrice - $lastPriceChange['price']) / $lastPriceChange['price']) * 100;
        
        // 가격 인상 확인 (10% 이상)
        if ($priceChangePercent >= 10) {
            $alerts[] = [
                'type' => 'price_increase',
                'product_id' => $product->id,
                'product_name' => $product->name,
                'message' => "가격이 10% 이상 인상되었습니다. (변화율: " . round($priceChangePercent, 2) . "%)",
                'current_price' => $currentPrice,
                'previous_price' => $lastPriceChange['price'],
                'change_percent' => round($priceChangePercent, 2),
                'change_date' => $lastPriceChange['date'],
                'created_at' => Carbon::now()
            ];
        }
        
        // 가격 인하 확인 (10% 이상)
        if ($priceChangePercent <= -10) {
            $alerts[] = [
                'type' => 'price_decrease',
                'product_id' => $product->id,
                'product_name' => $product->name,
                'message' => "가격이 10% 이상 인하되었습니다. (변화율: " . round($priceChangePercent, 2) . "%)",
                'current_price' => $currentPrice,
                'previous_price' => $lastPriceChange['price'],
                'change_percent' => round($priceChangePercent, 2),
                'change_date' => $lastPriceChange['date'],
                'created_at' => Carbon::now()
            ];
        }
        
        return $alerts;
    }
    
    /**
     * 모니터링할 제품 가져오기
     */
    protected function getProductsToMonitor(?int $userId = null): Collection
    {
        // 사용자 ID가 제공된 경우 해당 사용자의 제품만 가져오기
        if ($userId) {
            return Product::where('user_id', $userId)->get();
        }
        
        // 모든 활성 제품 가져오기
        return Product::where('status', 'active')->get();
    }
    
    /**
     * 성과 데이터 가져오기
     */
    protected function getPerformanceData(Product $product, Carbon $startDate, Carbon $endDate): array
    {
        // 실제 구현에서는 DB에서 데이터 가져오기
        // 여기서는 예시 데이터 반환
        return [
            'sales_count' => rand(10, 100),
            'revenue' => rand(100000, 1000000),
            'margin_rate' => rand(10, 40),
            'view_count' => rand(100, 1000),
            'conversion_rate' => rand(1, 10)
        ];
    }
    
    /**
     * 최근 리뷰 가져오기
     */
    protected function getRecentReviews(Product $product): Collection
    {
        // 실제 구현에서는 DB에서 데이터 가져오기
        // 여기서는 빈 컬렉션 반환
        return collect([]);
    }
    
    /**
     * 부정적인 키워드 감지
     */
    protected function detectNegativeKeywords(Collection $reviews): array
    {
        $negativeKeywords = [];
        $keywordPatterns = [
            '품질' => ['나쁘', '좋지 않', '형편없', '불량'],
            '배송' => ['늦', '느리', '지연'],
            '가격' => ['비싸', '과도', '부담'],
            '서비스' => ['불친절', '무성의', '불만족']
        ];
        
        foreach ($reviews as $review) {
            $content = $review->content ?? '';
            
            foreach ($keywordPatterns as $category => $patterns) {
                foreach ($patterns as $pattern) {
                    if (strpos($content, $pattern) !== false) {
                        if (!isset($negativeKeywords[$category])) {
                            $negativeKeywords[$category] = 0;
                        }
                        $negativeKeywords[$category]++;
                        break;
                    }
                }
            }
        }
        
        // 빈도수 기준으로 정렬
        arsort($negativeKeywords);
        
        return $negativeKeywords;
    }
    
    /**
     * 일평균 판매량 가져오기
     */
    protected function getAverageSalesPerDay(Product $product): float
    {
        // 실제 구현에서는 DB에서 데이터 가져오기
        // 여기서는 예시 데이터 반환
        return rand(1, 10) / 2;
    }
    
    /**
     * 가격 이력 가져오기
     */
    protected function getPriceHistory(Product $product): array
    {
        // 실제 구현에서는 DB에서 데이터 가져오기
        // 여기서는 예시 데이터 반환
        return [
            [
                'price' => $product->price * 0.9,
                'date' => Carbon::now()->subDays(30)
            ]
        ];
    }
    
    /**
     * 임계값 설정 업데이트
     */
    public function updateThresholds(array $newThresholds): bool
    {
        try {
            foreach ($newThresholds as $key => $value) {
                if (array_key_exists($key, $this->thresholds)) {
                    $this->thresholds[$key] = $value;
                }
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('임계값 업데이트 중 오류 발생: ' . $e->getMessage(), [
                'new_thresholds' => $newThresholds,
                'exception' => $e
            ]);
            
            return false;
        }
    }
    
    /**
     * 사용자별 임계값 설정 가져오기
     */
    public function getUserThresholds(int $userId): array
    {
        $cacheKey = "user_thresholds_{$userId}";
        
        return Cache::remember($cacheKey, now()->addDays(7), function () {
            return $this->thresholds;
        });
    }
} 