<?php

namespace App\Services\Recommendation;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class OpportunityScoreCalculator
{
    // 기본 가중치 설정
    protected array $defaultWeights = [
        'demand' => 0.25,
        'competition' => 0.20,
        'growth' => 0.20,
        'margin' => 0.25,
        'seasonality' => 0.10
    ];
    
    // 사용자별 가중치 캐시 키
    protected string $userWeightsCacheKey = 'opportunity_score_weights_user_';
    
    /**
     * 제품 기회 점수 계산
     */
    public function calculateScore(array $product, array $additionalData = []): float
    {
        try {
            // 기본 점수 요소 추출
            $demandScore = $this->calculateDemandScore($product, $additionalData);
            $competitionScore = $this->calculateCompetitionScore($product, $additionalData);
            $growthScore = $this->calculateGrowthScore($product, $additionalData);
            $marginScore = $this->calculateMarginScore($product, $additionalData);
            $seasonalityScore = $this->calculateSeasonalityScore($product, $additionalData);
            
            // 사용자 ID가 제공된 경우 사용자별 가중치 사용
            $weights = isset($additionalData['user_id']) 
                ? $this->getUserWeights($additionalData['user_id']) 
                : $this->defaultWeights;
            
            // 가중 평균 계산
            $score = 
                ($demandScore * $weights['demand']) +
                ($competitionScore * $weights['competition']) +
                ($growthScore * $weights['growth']) +
                ($marginScore * $weights['margin']) +
                ($seasonalityScore * $weights['seasonality']);
            
            // 0-100 범위로 정규화
            return min(100, max(0, $score * 100));
        } catch (\Exception $e) {
            Log::error('기회 점수 계산 중 오류 발생: ' . $e->getMessage(), [
                'product' => $product,
                'additional_data' => $additionalData,
                'exception' => $e
            ]);
            
            return 0;
        }
    }
    
    /**
     * 카테고리 기반 기회 점수 계산
     */
    public function calculateCategoryScore(array $product, ?array $competitionData): float
    {
        try {
            // 추가 데이터에 경쟁 정보 포함
            $additionalData = ['competition_data' => $competitionData];
            
            // 기본 점수 계산 로직 활용
            return $this->calculateScore($product, $additionalData);
        } catch (\Exception $e) {
            Log::error('카테고리 기회 점수 계산 중 오류 발생: ' . $e->getMessage(), [
                'product' => $product,
                'competition_data' => $competitionData,
                'exception' => $e
            ]);
            
            return 0;
        }
    }
    
    /**
     * 가격 차이 기반 기회 점수 계산
     */
    public function calculatePriceGapScore(array $opportunity): float
    {
        try {
            // 가격 차이 비율 계산
            $wholesalePrice = $opportunity['wholesale_price'] ?? 0;
            $retailPrice = $opportunity['retail_price'] ?? 0;
            
            if ($wholesalePrice <= 0 || $retailPrice <= 0) {
                return 0;
            }
            
            $priceGapRatio = ($retailPrice - $wholesalePrice) / $retailPrice;
            
            // 기본 점수 요소 계산
            $marginScore = min(1, max(0, $priceGapRatio * 2)); // 50% 이상 차이면 만점
            $demandScore = $opportunity['demand_score'] ?? 0.5;
            $competitionScore = $opportunity['competition_score'] ?? 0.5;
            
            // 가중 평균 계산
            $score = 
                ($marginScore * 0.6) + 
                ($demandScore * 0.25) + 
                ($competitionScore * 0.15);
            
            // 0-100 범위로 정규화
            return min(100, max(0, $score * 100));
        } catch (\Exception $e) {
            Log::error('가격 차이 기회 점수 계산 중 오류 발생: ' . $e->getMessage(), [
                'opportunity' => $opportunity,
                'exception' => $e
            ]);
            
            return 0;
        }
    }
    
    /**
     * 사용자별 가중치 업데이트
     */
    public function updateUserWeights(int $userId, array $weights): bool
    {
        try {
            // 가중치 합이 1이 되도록 정규화
            $totalWeight = array_sum($weights);
            $normalizedWeights = [];
            
            foreach ($weights as $key => $weight) {
                $normalizedWeights[$key] = $weight / $totalWeight;
            }
            
            // 캐시에 저장
            $cacheKey = $this->userWeightsCacheKey . $userId;
            Cache::put($cacheKey, $normalizedWeights, now()->addMonths(3));
            
            return true;
        } catch (\Exception $e) {
            Log::error('사용자 가중치 업데이트 중 오류 발생: ' . $e->getMessage(), [
                'user_id' => $userId,
                'weights' => $weights,
                'exception' => $e
            ]);
            
            return false;
        }
    }
    
    /**
     * 사용자별 가중치 가져오기
     */
    protected function getUserWeights(int $userId): array
    {
        $cacheKey = $this->userWeightsCacheKey . $userId;
        
        return Cache::remember($cacheKey, now()->addDays(30), function () {
            return $this->defaultWeights;
        });
    }
    
    /**
     * 수요 점수 계산
     */
    protected function calculateDemandScore(array $product, array $additionalData): float
    {
        // 검색 볼륨, 판매량, 조회수 등을 기반으로 수요 점수 계산
        $searchVolume = $product['search_volume'] ?? 0;
        $viewCount = $product['view_count'] ?? 0;
        $salesCount = $product['sales_count'] ?? 0;
        
        // 각 지표를 0-1 범위로 정규화
        $normalizedSearchVolume = $this->normalizeValue($searchVolume, 0, 10000, 0, 1);
        $normalizedViewCount = $this->normalizeValue($viewCount, 0, 5000, 0, 1);
        $normalizedSalesCount = $this->normalizeValue($salesCount, 0, 1000, 0, 1);
        
        // 가중 평균 계산
        return 
            ($normalizedSearchVolume * 0.4) + 
            ($normalizedViewCount * 0.3) + 
            ($normalizedSalesCount * 0.3);
    }
    
    /**
     * 경쟁 점수 계산
     */
    protected function calculateCompetitionScore(array $product, array $additionalData): float
    {
        // 경쟁 강도, 판매자 수, 리뷰 수 등을 기반으로 경쟁 점수 계산
        $competitionLevel = $product['competition_level'] ?? 5; // 1-10 범위 (10이 가장 경쟁 심함)
        $sellerCount = $product['seller_count'] ?? 0;
        $reviewCount = $product['review_count'] ?? 0;
        
        // 경쟁 데이터가 제공된 경우 활용
        if (isset($additionalData['competition_data'])) {
            $competitionData = $additionalData['competition_data'];
            $competitionLevel = $competitionData['competition_level'] ?? $competitionLevel;
            $sellerCount = $competitionData['seller_count'] ?? $sellerCount;
        }
        
        // 각 지표를 0-1 범위로 정규화 (경쟁이 적을수록 높은 점수)
        $normalizedCompetitionLevel = 1 - $this->normalizeValue($competitionLevel, 1, 10, 0, 1);
        $normalizedSellerCount = 1 - $this->normalizeValue($sellerCount, 0, 500, 0, 1);
        $normalizedReviewCount = 1 - $this->normalizeValue($reviewCount, 0, 10000, 0, 1);
        
        // 가중 평균 계산
        return 
            ($normalizedCompetitionLevel * 0.5) + 
            ($normalizedSellerCount * 0.3) + 
            ($normalizedReviewCount * 0.2);
    }
    
    /**
     * 성장 점수 계산
     */
    protected function calculateGrowthScore(array $product, array $additionalData): float
    {
        // 성장률, 트렌드 지수 등을 기반으로 성장 점수 계산
        $growthRate = $product['growth_rate'] ?? 0; // 백분율 (%)
        $trendIndex = $product['trend_index'] ?? 0; // 0-100 범위
        
        // 각 지표를 0-1 범위로 정규화
        $normalizedGrowthRate = $this->normalizeValue($growthRate, -20, 100, 0, 1);
        $normalizedTrendIndex = $this->normalizeValue($trendIndex, 0, 100, 0, 1);
        
        // 가중 평균 계산
        return 
            ($normalizedGrowthRate * 0.6) + 
            ($normalizedTrendIndex * 0.4);
    }
    
    /**
     * 마진 점수 계산
     */
    protected function calculateMarginScore(array $product, array $additionalData): float
    {
        // 마진율, 가격 등을 기반으로 마진 점수 계산
        $marginRate = $product['margin_rate'] ?? 0; // 백분율 (%)
        $price = $product['price'] ?? 0;
        
        // 각 지표를 0-1 범위로 정규화
        $normalizedMarginRate = $this->normalizeValue($marginRate, 0, 70, 0, 1);
        
        // 가격 범위에 따른 점수 계산 (너무 저렴하거나 비싸지 않은 중간 가격대가 높은 점수)
        $priceScore = 0;
        if ($price > 0) {
            if ($price < 5000) {
                $priceScore = $this->normalizeValue($price, 0, 5000, 0, 0.7);
            } else if ($price <= 50000) {
                $priceScore = $this->normalizeValue($price, 5000, 50000, 0.7, 1);
            } else if ($price <= 200000) {
                $priceScore = $this->normalizeValue($price, 50000, 200000, 1, 0.5);
            } else {
                $priceScore = $this->normalizeValue($price, 200000, 1000000, 0.5, 0.1);
            }
        }
        
        // 가중 평균 계산
        return 
            ($normalizedMarginRate * 0.7) + 
            ($priceScore * 0.3);
    }
    
    /**
     * 계절성 점수 계산
     */
    protected function calculateSeasonalityScore(array $product, array $additionalData): float
    {
        // 계절성, 현재 시즌 관련성 등을 기반으로 계절성 점수 계산
        $seasonality = $product['seasonality'] ?? 0; // 0-1 범위 (1이 높은 계절성)
        $currentSeasonRelevance = $product['current_season_relevance'] ?? 0.5; // 0-1 범위
        
        // 계절성이 낮으면 높은 점수, 계절성이 높으면 현재 시즌 관련성에 따라 점수 부여
        if ($seasonality < 0.3) {
            return 0.8; // 계절성이 낮으면 안정적인 판매 가능
        } else {
            return $currentSeasonRelevance;
        }
    }
    
    /**
     * 값을 지정된 범위로 정규화
     */
    protected function normalizeValue(float $value, float $minInput, float $maxInput, float $minOutput, float $maxOutput): float
    {
        if ($minInput >= $maxInput) {
            return $minOutput;
        }
        
        $value = min($maxInput, max($minInput, $value));
        $ratio = ($value - $minInput) / ($maxInput - $minInput);
        
        return $minOutput + $ratio * ($maxOutput - $minOutput);
    }
} 