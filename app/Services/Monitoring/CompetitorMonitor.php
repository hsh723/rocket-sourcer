<?php namespace App\Services\Monitoring;

use App\Models\Product;
use App\Models\Category;
use App\Models\Competitor;
use App\Services\Crawler\CoupangCrawlerService;
use App\Services\Crawler\NaverCrawlerService;
use App\Services\Monitoring\AlertService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CompetitorMonitor
{
    protected CoupangCrawlerService $coupangCrawlerService;
    protected NaverCrawlerService $naverCrawlerService;
    protected AlertService $alertService;
    
    // 경쟁사 모니터링 임계값 설정
    protected array $thresholds = [
        'price_decrease_percent' => 10,      // 가격 인하 임계값 (%)
        'price_increase_percent' => 15,      // 가격 인상 임계값 (%)
        'rating_change' => 0.5,              // 평점 변화 임계값
        'review_increase_percent' => 20,     // 리뷰 증가 임계값 (%)
        'rank_change' => 5,                  // 순위 변화 임계값
        'stock_status_change' => true,       // 재고 상태 변화 모니터링 여부
        'new_competitor_alert' => true,      // 새로운 경쟁사 알림 여부
        'promotion_alert' => true            // 프로모션 알림 여부
    ];
    
    /**
     * 모니터 생성자
     */
    public function __construct(
        CoupangCrawlerService $coupangCrawlerService,
        NaverCrawlerService $naverCrawlerService,
        AlertService $alertService
    ) {
        $this->coupangCrawlerService = $coupangCrawlerService;
        $this->naverCrawlerService = $naverCrawlerService;
        $this->alertService = $alertService;
    }
    
    /**
     * 모든 제품의 경쟁사 모니터링
     */
    public function monitorAllCompetitors(int $userId = null): array
    {
        try {
            $startTime = microtime(true);
            Log::info('모든 제품의 경쟁사 모니터링 시작');
            
            // 모니터링할 제품 가져오기
            $products = $this->getProductsToMonitor($userId);
            
            $alerts = [];
            $processedCount = 0;
            
            foreach ($products as $product) {
                // 제품별 경쟁사 모니터링
                $productAlerts = $this->monitorProductCompetitors($product);
                
                if (!empty($productAlerts)) {
                    $alerts = array_merge($alerts, $productAlerts);
                }
                
                $processedCount++;
                
                // 50개 제품마다 로그 기록
                if ($processedCount % 50 === 0) {
                    Log::info("제품 {$processedCount}/{$products->count()} 경쟁사 모니터링 완료");
                }
            }
            
            $duration = round(microtime(true) - $startTime, 2);
            Log::info("모든 제품의 경쟁사 모니터링 완료. 처리 시간: {$duration}초, 처리된 제품: {$processedCount}, 생성된 알림: " . count($alerts));
            
            return [
                'processed_count' => $processedCount,
                'alerts_count' => count($alerts),
                'alerts' => $alerts,
                'duration' => $duration
            ];
        } catch (\Exception $e) {
            Log::error('경쟁사 모니터링 중 오류 발생: ' . $e->getMessage(), [
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
     * 단일 제품의 경쟁사 모니터링
     */
    public function monitorProductCompetitors(Product $product): array
    {
        try {
            $alerts = [];
            
            // 경쟁사 목록 가져오기
            $competitors = $this->getCompetitors($product);
            
            // 새로운 경쟁사 확인
            $newCompetitorAlerts = $this->checkForNewCompetitors($product, $competitors);
            if (!empty($newCompetitorAlerts)) {
                $alerts = array_merge($alerts, $newCompetitorAlerts);
            }
            
            // 기존 경쟁사 변화 모니터링
            foreach ($competitors as $competitor) {
                // 가격 변화 모니터링
                $priceAlerts = $this->monitorPriceChanges($product, $competitor);
                if (!empty($priceAlerts)) {
                    $alerts = array_merge($alerts, $priceAlerts);
                }
                
                // 평점 및 리뷰 변화 모니터링
                $reviewAlerts = $this->monitorReviewChanges($product, $competitor);
                if (!empty($reviewAlerts)) {
                    $alerts = array_merge($alerts, $reviewAlerts);
                }
                
                // 재고 상태 변화 모니터링
                $stockAlerts = $this->monitorStockChanges($product, $competitor);
                if (!empty($stockAlerts)) {
                    $alerts = array_merge($alerts, $stockAlerts);
                }
                
                // 순위 변화 모니터링
                $rankAlerts = $this->monitorRankChanges($product, $competitor);
                if (!empty($rankAlerts)) {
                    $alerts = array_merge($alerts, $rankAlerts);
                }
                
                // 프로모션 모니터링
                $promotionAlerts = $this->monitorPromotions($product, $competitor);
                if (!empty($promotionAlerts)) {
                    $alerts = array_merge($alerts, $promotionAlerts);
                }
            }
            
            return $alerts;
        } catch (\Exception $e) {
            Log::error('제품 경쟁사 모니터링 중 오류 발생: ' . $e->getMessage(), [
                'product_id' => $product->id,
                'exception' => $e
            ]);
            
            return [];
        }
    }
    
    /**
     * 카테고리 경쟁 분석
     */
    public function analyzeCategoryCompetition(Category $category): array
    {
        try {
            // 카테고리 내 경쟁사 데이터 수집
            $competitorData = $this->collectCategoryCompetitorData($category);
            
            // 경쟁 강도 분석
            $competitionIntensity = $this->calculateCompetitionIntensity($competitorData);
            
            // 가격 분포 분석
            $priceDistribution = $this->analyzePriceDistribution($competitorData);
            
            // 시장 점유율 분석
            $marketShare = $this->analyzeMarketShare($competitorData);
            
            // 리뷰 및 평점 분석
            $reviewAnalysis = $this->analyzeReviews($competitorData);
            
            return [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'competition_intensity' => $competitionIntensity,
                'price_distribution' => $priceDistribution,
                'market_share' => $marketShare,
                'review_analysis' => $reviewAnalysis,
                'competitor_count' => count($competitorData),
                'analyzed_at' => Carbon::now()
            ];
        } catch (\Exception $e) {
            Log::error('카테고리 경쟁 분석 중 오류 발생: ' . $e->getMessage(), [
                'category_id' => $category->id,
                'exception' => $e
            ]);
            
            return [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'error' => $e->getMessage(),
                'analyzed_at' => Carbon::now()
            ];
        }
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
     * 제품의 경쟁사 목록 가져오기
     */
    protected function getCompetitors(Product $product): Collection
    {
        // 캐시 키 생성
        $cacheKey = "competitors_product_{$product->id}";
        
        // 캐시에서 경쟁사 데이터 가져오기 또는 새로 수집
        return Cache::remember($cacheKey, now()->addHours(12), function () use ($product) {
            // 쿠팡에서 경쟁사 데이터 수집
            $coupangCompetitors = $this->coupangCrawlerService->getCompetitors($product->name, $product->category_id);
            
            // 네이버에서 경쟁사 데이터 수집
            $naverCompetitors = $this->naverCrawlerService->getCompetitors($product->name, $product->category_id);
            
            // 경쟁사 데이터 병합 및 중복 제거
            $allCompetitors = $coupangCompetitors->merge($naverCompetitors);
            
            // 기존 경쟁사 데이터 가져오기
            $existingCompetitors = Competitor::where('product_id', $product->id)->get();
            
            // 새로운 경쟁사 저장
            $this->saveNewCompetitors($product, $allCompetitors, $existingCompetitors);
            
            // 최신 경쟁사 데이터 반환
            return Competitor::where('product_id', $product->id)->get();
        });
    }
    
    /**
     * 새로운 경쟁사 저장
     */
    protected function saveNewCompetitors(Product $product, Collection $newCompetitors, Collection $existingCompetitors): void
    {
        $existingUrls = $existingCompetitors->pluck('url')->toArray();
        
        foreach ($newCompetitors as $competitor) {
            // 이미 존재하는 경쟁사인지 확인
            if (!in_array($competitor['url'], $existingUrls)) {
                // 새로운 경쟁사 저장
                Competitor::create([
                    'product_id' => $product->id,
                    'name' => $competitor['name'],
                    'url' => $competitor['url'],
                    'platform' => $competitor['platform'],
                    'price' => $competitor['price'],
                    'rating' => $competitor['rating'] ?? 0,
                    'review_count' => $competitor['review_count'] ?? 0,
                    'rank' => $competitor['rank'] ?? 0,
                    'stock_status' => $competitor['stock_status'] ?? 'in_stock',
                    'last_checked_at' => Carbon::now()
                ]);
            }
        }
    }
    
    /**
     * 새로운 경쟁사 확인
     */
    protected function checkForNewCompetitors(Product $product, Collection $competitors): array
    {
        if (!$this->thresholds['new_competitor_alert']) {
            return [];
        }
        
        $alerts = [];
        $lastCheckedAt = Carbon::now()->subDay();
        
        // 최근 추가된 경쟁사 확인
        $newCompetitors = $competitors->filter(function ($competitor) use ($lastCheckedAt) {
            return $competitor->created_at > $lastCheckedAt;
        });
        
        if ($newCompetitors->isNotEmpty()) {
            $alerts[] = [
                'type' => 'new_competitors',
                'product_id' => $product->id,
                'product_name' => $product->name,
                'message' => "새로운 경쟁사 " . $newCompetitors->count() . "개가 발견되었습니다.",
                'competitors' => $newCompetitors->map(function ($competitor) {
                    return [
                        'id' => $competitor->id,
                        'name' => $competitor->name,
                        'platform' => $competitor->platform,
                        'price' => $competitor->price,
                        'url' => $competitor->url
                    ];
                }),
                'created_at' => Carbon::now()
            ];
        }
        
        return $alerts;
    }
    
    /**
     * 가격 변화 모니터링
     */
    protected function monitorPriceChanges(Product $product, Competitor $competitor): array
    {
        $alerts = [];
        
        // 현재 가격 가져오기
        $currentPrice = $this->getCurrentCompetitorPrice($competitor);
        
        // 이전 가격
        $previousPrice = $competitor->price;
        
        if ($previousPrice <= 0 || $currentPrice <= 0) {
            return $alerts;
        }
        
        // 가격 변화율 계산
        $priceChangePercent = (($currentPrice - $previousPrice) / $previousPrice) * 100;
        
        // 가격 인하 확인
        if ($priceChangePercent <= -$this->thresholds['price_decrease_percent']) {
            $alerts[] = [
                'type' => 'competitor_price_decrease',
                'product_id' => $product->id,
                'product_name' => $product->name,
                'competitor_id' => $competitor->id,
                'competitor_name' => $competitor->name,
                'message' => "경쟁사 {$competitor->name}의 가격이 {$this->thresholds['price_decrease_percent']}% 이상 인하되었습니다. (변화율: " . round($priceChangePercent, 2) . "%)",
                'current_price' => $currentPrice,
                'previous_price' => $previousPrice,
                'change_percent' => round($priceChangePercent, 2),
                'platform' => $competitor->platform,
                'url' => $competitor->url,
                'created_at' => Carbon::now()
            ];
        }
        
        // 가격 인상 확인
        if ($priceChangePercent >= $this->thresholds['price_increase_percent']) {
            $alerts[] = [
                'type' => 'competitor_price_increase',
                'product_id' => $product->id,
                'product_name' => $product->name,
                'competitor_id' => $competitor->id,
                'competitor_name' => $competitor->name,
                'message' => "경쟁사 {$competitor->name}의 가격이 {$this->thresholds['price_increase_percent']}% 이상 인상되었습니다. (변화율: " . round($priceChangePercent, 2) . "%)",
                'current_price' => $currentPrice,
                'previous_price' => $previousPrice,
                'change_percent' => round($priceChangePercent, 2),
                'platform' => $competitor->platform,
                'url' => $competitor->url,
                'created_at' => Carbon::now()
            ];
        }
        
        // 가격 변경 사항이 있으면 경쟁사 정보 업데이트
        if (abs($priceChangePercent) > 1) {
            $competitor->price = $currentPrice;
            $competitor->last_checked_at = Carbon::now();
            $competitor->save();
        }
        
        return $alerts;
    }
    
    /**
     * 평점 및 리뷰 변화 모니터링
     */
    protected function monitorReviewChanges(Product $product, Competitor $competitor): array
    {
        $alerts = [];
        
        // 현재 평점 및 리뷰 수 가져오기
        $currentData = $this->getCurrentCompetitorReviewData($competitor);
        
        // 이전 데이터
        $previousRating = $competitor->rating;
        $previousReviewCount = $competitor->review_count;
        
        // 평점 변화 확인
        $ratingChange = abs($currentData['rating'] - $previousRating);
        if ($ratingChange >= $this->thresholds['rating_change'] && $previousRating > 0) {
            $direction = $currentData['rating'] > $previousRating ? '상승' : '하락';
            $alerts[] = [
                'type' => 'competitor_rating_change',
                'product_id' => $product->id,
                'product_name' => $product->name,
                'competitor_id' => $competitor->id,
                'competitor_name' => $competitor->name,
                'message' => "경쟁사 {$competitor->name}의 평점이 {$this->thresholds['rating_change']} 이상 {$direction}했습니다. (변화: " . round($ratingChange, 2) . ")",
                'current_rating' => $currentData['rating'],
                'previous_rating' => $previousRating,
                'change' => round($ratingChange, 2),
                'direction' => $direction,
                'platform' => $competitor->platform,
                'url' => $competitor->url,
                'created_at' => Carbon::now()
            ];
        }
        
        // 리뷰 수 변화 확인
        if ($previousReviewCount > 0) {
            $reviewChangePercent = (($currentData['review_count'] - $previousReviewCount) / $previousReviewCount) * 100;
            
            if ($reviewChangePercent >= $this->thresholds['review_increase_percent']) {
                $alerts[] = [
                    'type' => 'competitor_review_increase',
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'competitor_id' => $competitor->id,
                    'competitor_name' => $competitor->name,
                    'message' => "경쟁사 {$competitor->name}의 리뷰 수가 {$this->thresholds['review_increase_percent']}% 이상 증가했습니다. (변화율: " . round($reviewChangePercent, 2) . "%)",
                    'current_review_count' => $currentData['review_count'],
                    'previous_review_count' => $previousReviewCount,
                    'change_percent' => round($reviewChangePercent, 2),
                    'platform' => $competitor->platform,
                    'url' => $competitor->url,
                    'created_at' => Carbon::now()
                ];
            }
        }
        
        // 변경 사항이 있으면 경쟁사 정보 업데이트
        if ($ratingChange > 0 || ($previousReviewCount > 0 && $currentData['review_count'] != $previousReviewCount)) {
            $competitor->rating = $currentData['rating'];
            $competitor->review_count = $currentData['review_count'];
            $competitor->last_checked_at = Carbon::now();
            $competitor->save();
        }
        
        return $alerts;
    }
    
    /**
     * 재고 상태 변화 모니터링
     */
    protected function monitorStockChanges(Product $product, Competitor $competitor): array
    {
        if (!$this->thresholds['stock_status_change']) {
            return [];
        }
        
        $alerts = [];
        
        // 현재 재고 상태 가져오기
        $currentStockStatus = $this->getCurrentCompetitorStockStatus($competitor);
        
        // 이전 재고 상태
        $previousStockStatus = $competitor->stock_status;
        
        // 재고 상태 변화 확인
        if ($currentStockStatus != $previousStockStatus) {
            $statusText = $currentStockStatus == 'in_stock' ? '입고' : '품절';
            $alerts[] = [
                'type' => 'competitor_stock_change',
                'product_id' => $product->id,
                'product_name' => $product->name,
                'competitor_id' => $competitor->id,
                'competitor_name' => $competitor->name,
                'message' => "경쟁사 {$competitor->name}의 재고 상태가 변경되었습니다. (현재: {$statusText})",
                'current_stock_status' => $currentStockStatus,
                'previous_stock_status' => $previousStockStatus,
                'platform' => $competitor->platform,
                'url' => $competitor->url,
                'created_at' => Carbon::now()
            ];
            
            // 경쟁사 정보 업데이트
            $competitor->stock_status = $currentStockStatus;
            $competitor->last_checked_at = Carbon::now();
            $competitor->save();
        }
        
        return $alerts;
    }
    
    /**
     * 순위 변화 모니터링
     */
    protected function monitorRankChanges(Product $product, Competitor $competitor): array
    {
        $alerts = [];
        
        // 현재 순위 가져오기
        $currentRank = $this->getCurrentCompetitorRank($competitor);
        
        // 이전 순위
        $previousRank = $competitor->rank;
        
        // 순위가 없는 경우 스킵
        if ($currentRank <= 0 || $previousRank <= 0) {
            return $alerts;
        }
        
        // 순위 변화 확인
        $rankChange = $previousRank - $currentRank;
        
        if (abs($rankChange) >= $this->thresholds['rank_change']) {
            $direction = $rankChange > 0 ? '상승' : '하락';
            $alerts[] = [
                'type' => 'competitor_rank_change',
                'product_id' => $product->id,
                'product_name' => $product->name,
                'competitor_id' => $competitor->id,
                'competitor_name' => $competitor->name,
                'message' => "경쟁사 {$competitor->name}의 순위가 {$this->thresholds['rank_change']} 이상 {$direction}했습니다. (변화: " . abs($rankChange) . ")",
                'current_rank' => $currentRank,
                'previous_rank' => $previousRank,
                'change' => $rankChange,
                'direction' => $direction,
                'platform' => $competitor->platform,
                'url' => $competitor->url,
                'created_at' => Carbon::now()
            ];
            
            // 경쟁사 정보 업데이트
            $competitor->rank = $currentRank;
            $competitor->last_checked_at = Carbon::now();
            $competitor->save();
        }
        
        return $alerts;
    }
    
    /**
     * 프로모션 모니터링
     */
    protected function monitorPromotions(Product $product, Competitor $competitor): array
    {
        if (!$this->thresholds['promotion_alert']) {
            return [];
        }
        
        $alerts = [];
        
        // 현재 프로모션 정보 가져오기
        $currentPromotions = $this->getCurrentCompetitorPromotions($competitor);
        
        if (empty($currentPromotions)) {
            return $alerts;
        }
        
        // 새로운 프로모션 확인
        foreach ($currentPromotions as $promotion) {
            $alerts[] = [
                'type' => 'competitor_promotion',
                'product_id' => $product->id,
                'product_name' => $product->name,
                'competitor_id' => $competitor->id,
                'competitor_name' => $competitor->name,
                'message' => "경쟁사 {$competitor->name}에서 새로운 프로모션이 발견되었습니다: {$promotion['title']}",
                'promotion' => $promotion,
                'platform' => $competitor->platform,
                'url' => $competitor->url,
                'created_at' => Carbon::now()
            ];
        }
        
        return $alerts;
    }
    
    /**
     * 현재 경쟁사 가격 가져오기
     */
    protected function getCurrentCompetitorPrice(Competitor $competitor): float
    {
        // 플랫폼에 따라 적절한 크롤러 서비스 사용
        if ($competitor->platform === 'coupang') {
            $data = $this->coupangCrawlerService->getProductDetails($competitor->url);
            return $data['price'] ?? 0;
        } else if ($competitor->platform === 'naver') {
            $data = $this->naverCrawlerService->getProductDetails($competitor->url);
            return $data['price'] ?? 0;
        }
        
        return 0;
    }
    
    /**
     * 현재 경쟁사 평점 및 리뷰 데이터 가져오기
     */
    protected function getCurrentCompetitorReviewData(Competitor $competitor): array
    {
        // 플랫폼에 따라 적절한 크롤러 서비스 사용
        if ($competitor->platform === 'coupang') {
            $data = $this->coupangCrawlerService->getProductDetails($competitor->url);
            return [
                'rating' => $data['rating'] ?? 0,
                'review_count' => $data['review_count'] ?? 0
            ];
        } else if ($competitor->platform === 'naver') {
            $data = $this->naverCrawlerService->getProductDetails($competitor->url);
            return [
                'rating' => $data['rating'] ?? 0,
                'review_count' => $data['review_count'] ?? 0
            ];
        }
        
        return [
            'rating' => 0,
            'review_count' => 0
        ];
    }
    
    /**
     * 현재 경쟁사 재고 상태 가져오기
     */
    protected function getCurrentCompetitorStockStatus(Competitor $competitor): string
    {
        // 플랫폼에 따라 적절한 크롤러 서비스 사용
        if ($competitor->platform === 'coupang') {
            $data = $this->coupangCrawlerService->getProductDetails($competitor->url);
            return $data['stock_status'] ?? 'unknown';
        } else if ($competitor->platform === 'naver') {
            $data = $this->naverCrawlerService->getProductDetails($competitor->url);
            return $data['stock_status'] ?? 'unknown';
        }
        
        return 'unknown';
    }
    
    /**
     * 현재 경쟁사 순위 가져오기
     */
    protected function getCurrentCompetitorRank(Competitor $competitor): int
    {
        // 플랫폼에 따라 적절한 크롤러 서비스 사용
        if ($competitor->platform === 'coupang') {
            $data = $this->coupangCrawlerService->getProductRank($competitor->url, $competitor->name);
            return $data['rank'] ?? 0;
        } else if ($competitor->platform === 'naver') {
            $data = $this->naverCrawlerService->getProductRank($competitor->url, $competitor->name);
            return $data['rank'] ?? 0;
        }
        
        return 0;
    }
    
    /**
     * 현재 경쟁사 프로모션 가져오기
     */
    protected function getCurrentCompetitorPromotions(Competitor $competitor): array
    {
        // 플랫폼에 따라 적절한 크롤러 서비스 사용
        if ($competitor->platform === 'coupang') {
            $data = $this->coupangCrawlerService->getProductPromotions($competitor->url);
            return $data['promotions'] ?? [];
        } else if ($competitor->platform === 'naver') {
            $data = $this->naverCrawlerService->getProductPromotions($competitor->url);
            return $data['promotions'] ?? [];
        }
        
        return [];
    }
    
    /**
     * 카테고리 내 경쟁사 데이터 수집
     */
    protected function collectCategoryCompetitorData(Category $category): array
    {
        // 캐시 키 생성
        $cacheKey = "category_competitors_{$category->id}";
        
        // 캐시에서 데이터 가져오기 또는 새로 수집
        return Cache::remember($cacheKey, now()->addHours(24), function () use ($category) {
            try {
                // 쿠팡에서 카테고리 데이터 수집
                $coupangData = $this->coupangCrawlerService->getCategoryCompetitors($category->id);
                
                // 네이버에서 카테고리 데이터 수집
                $naverData = $this->naverCrawlerService->getCategoryCompetitors($category->id);
                
                // 데이터 병합
                return array_merge($coupangData, $naverData);
            } catch (\Exception $e) {
                Log::error('카테고리 경쟁사 데이터 수집 중 오류 발생: ' . $e->getMessage(), [
                    'category_id' => $category->id,
                    'exception' => $e
                ]);
                
                return [];
            }
        });
    }
    
    /**
     * 경쟁 강도 계산
     */
    protected function calculateCompetitionIntensity(array $competitorData): array
    {
        if (empty($competitorData)) {
            return [
                'level' => 0,
                'score' => 0,
                'seller_count' => 0,
                'product_count' => 0,
                'price_range' => [
                    'min' => 0,
                    'max' => 0,
                    'avg' => 0
                ]
            ];
        }
        
        try {
            // 판매자 수 계산
            $sellerCount = count(array_unique(array_column($competitorData, 'seller_name')));
            
            // 제품 수 계산
            $productCount = count($competitorData);
            
            // 가격 범위 계산
            $prices = array_column($competitorData, 'price');
            $minPrice = min($prices);
            $maxPrice = max($prices);
            $avgPrice = array_sum($prices) / count($prices);
            
            // 리뷰 수 계산
            $totalReviews = array_sum(array_column($competitorData, 'review_count'));
            
            // 경쟁 강도 점수 계산 (1-10 범위)
            $score = $this->normalizeCompetitionScore($sellerCount, $productCount, $totalReviews);
            
            // 경쟁 강도 레벨 결정
            $level = $this->determineCompetitionLevel($score);
            
            return [
                'level' => $level,
                'score' => $score,
                'seller_count' => $sellerCount,
                'product_count' => $productCount,
                'total_reviews' => $totalReviews,
                'price_range' => [
                    'min' => $minPrice,
                    'max' => $maxPrice,
                    'avg' => $avgPrice
                ]
            ];
        } catch (\Exception $e) {
            Log::error('경쟁 강도 계산 중 오류 발생: ' . $e->getMessage(), [
                'competitor_data_count' => count($competitorData),
                'exception' => $e
            ]);
            
            return [
                'level' => 0,
                'score' => 0,
                'seller_count' => 0,
                'product_count' => 0,
                'price_range' => [
                    'min' => 0,
                    'max' => 0,
                    'avg' => 0
                ],
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 경쟁 점수 정규화
     */
    protected function normalizeCompetitionScore(int $sellerCount, int $productCount, int $totalReviews): float
    {
        // 판매자 수 점수 (최대 10점)
        $sellerScore = min(10, $sellerCount / 5);
        
        // 제품 수 점수 (최대 10점)
        $productScore = min(10, $productCount / 20);
        
        // 리뷰 수 점수 (최대 10점)
        $reviewScore = min(10, $totalReviews / 1000);
        
        // 가중 평균 계산
        $weightedScore = ($sellerScore * 0.4) + ($productScore * 0.3) + ($reviewScore * 0.3);
        
        // 1-10 범위로 정규화
        return max(1, min(10, $weightedScore));
    }
    
    /**
     * 경쟁 강도 레벨 결정
     */
    protected function determineCompetitionLevel(float $score): string
    {
        if ($score < 3) {
            return '낮음';
        } else if ($score < 6) {
            return '중간';
        } else if ($score < 8) {
            return '높음';
        } else {
            return '매우 높음';
        }
    }
    
    /**
     * 가격 분포 분석
     */
    protected function analyzePriceDistribution(array $competitorData): array
    {
        if (empty($competitorData)) {
            return [
                'ranges' => [],
                'distribution' => []
            ];
        }
        
        try {
            // 가격 추출
            $prices = array_column($competitorData, 'price');
            
            // 가격 범위 설정
            $minPrice = min($prices);
            $maxPrice = max($prices);
            
            // 가격 범위가 너무 작으면 기본값 설정
            if ($maxPrice - $minPrice < 10000) {
                $minPrice = max(0, $minPrice - 5000);
                $maxPrice = $maxPrice + 5000;
            }
            
            // 가격 구간 설정 (5개 구간)
            $rangeSize = ($maxPrice - $minPrice) / 5;
            $ranges = [];
            $distribution = [];
            
            for ($i = 0; $i < 5; $i++) {
                $rangeStart = $minPrice + ($i * $rangeSize);
                $rangeEnd = $rangeStart + $rangeSize;
                
                $rangeLabel = number_format($rangeStart) . '원 ~ ' . number_format($rangeEnd) . '원';
                $ranges[] = $rangeLabel;
                
                // 해당 구간에 속하는 제품 수 계산
                $count = count(array_filter($prices, function ($price) use ($rangeStart, $rangeEnd) {
                    return $price >= $rangeStart && $price < $rangeEnd;
                }));
                
                $distribution[] = [
                    'range' => $rangeLabel,
                    'count' => $count,
                    'percentage' => count($prices) > 0 ? ($count / count($prices)) * 100 : 0
                ];
            }
            
            return [
                'ranges' => $ranges,
                'distribution' => $distribution,
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
                'avg_price' => array_sum($prices) / count($prices)
            ];
        } catch (\Exception $e) {
            Log::error('가격 분포 분석 중 오류 발생: ' . $e->getMessage(), [
                'competitor_data_count' => count($competitorData),
                'exception' => $e
            ]);
            
            return [
                'ranges' => [],
                'distribution' => [],
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 시장 점유율 분석
     */
    protected function analyzeMarketShare(array $competitorData): array
    {
        if (empty($competitorData)) {
            return [
                'sellers' => [],
                'platforms' => []
            ];
        }
        
        try {
            // 판매자별 제품 수 계산
            $sellerCounts = [];
            foreach ($competitorData as $competitor) {
                $sellerName = $competitor['seller_name'] ?? '알 수 없음';
                if (!isset($sellerCounts[$sellerName])) {
                    $sellerCounts[$sellerName] = 0;
                }
                $sellerCounts[$sellerName]++;
            }
            
            // 판매자별 점유율 계산
            $totalProducts = count($competitorData);
            $sellerShares = [];
            
            foreach ($sellerCounts as $seller => $count) {
                $sellerShares[] = [
                    'seller' => $seller,
                    'product_count' => $count,
                    'percentage' => ($count / $totalProducts) * 100
                ];
            }
            
            // 점유율 기준 내림차순 정렬
            usort($sellerShares, function ($a, $b) {
                return $b['percentage'] <=> $a['percentage'];
            });
            
            // 상위 10개만 유지하고 나머지는 '기타'로 통합
            $topSellers = array_slice($sellerShares, 0, 10);
            $otherSellers = array_slice($sellerShares, 10);
            
            if (!empty($otherSellers)) {
                $otherCount = array_sum(array_column($otherSellers, 'product_count'));
                $otherPercentage = array_sum(array_column($otherSellers, 'percentage'));
                
                $topSellers[] = [
                    'seller' => '기타',
                    'product_count' => $otherCount,
                    'percentage' => $otherPercentage
                ];
            }
            
            // 플랫폼별 점유율 계산
            $platformCounts = [];
            foreach ($competitorData as $competitor) {
                $platform = $competitor['platform'] ?? '알 수 없음';
                if (!isset($platformCounts[$platform])) {
                    $platformCounts[$platform] = 0;
                }
                $platformCounts[$platform]++;
            }
            
            $platformShares = [];
            foreach ($platformCounts as $platform => $count) {
                $platformShares[] = [
                    'platform' => $platform,
                    'product_count' => $count,
                    'percentage' => ($count / $totalProducts) * 100
                ];
            }
            
            return [
                'sellers' => $topSellers,
                'platforms' => $platformShares,
                'total_products' => $totalProducts
            ];
        } catch (\Exception $e) {
            Log::error('시장 점유율 분석 중 오류 발생: ' . $e->getMessage(), [
                'competitor_data_count' => count($competitorData),
                'exception' => $e
            ]);
            
            return [
                'sellers' => [],
                'platforms' => [],
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 리뷰 및 평점 분석
     */
    protected function analyzeReviews(array $competitorData): array
    {
        if (empty($competitorData)) {
            return [
                'avg_rating' => 0,
                'rating_distribution' => [],
                'total_reviews' => 0
            ];
        }
        
        try {
            // 평점 및 리뷰 수 추출
            $ratings = array_column($competitorData, 'rating');
            $reviewCounts = array_column($competitorData, 'review_count');
            
            // 유효한 평점만 필터링
            $validRatings = array_filter($ratings, function ($rating) {
                return $rating > 0;
            });
            
            // 평균 평점 계산
            $avgRating = !empty($validRatings) ? array_sum($validRatings) / count($validRatings) : 0;
            
            // 총 리뷰 수 계산
            $totalReviews = array_sum($reviewCounts);
            
            // 평점 분포 계산
            $ratingDistribution = [
                '5점' => 0,
                '4점' => 0,
                '3점' => 0,
                '2점' => 0,
                '1점' => 0
            ];
            
            foreach ($validRatings as $rating) {
                $roundedRating = round($rating);
                if ($roundedRating >= 1 && $roundedRating <= 5) {
                    $ratingDistribution[$roundedRating . '점']++;
                }
            }
            
            // 백분율 계산
            $ratingPercentages = [];
            $validRatingCount = count($validRatings);
            
            if ($validRatingCount > 0) {
                foreach ($ratingDistribution as $label => $count) {
                    $ratingPercentages[] = [
                        'rating' => $label,
                        'count' => $count,
                        'percentage' => ($count / $validRatingCount) * 100
                    ];
                }
            }
            
            return [
                'avg_rating' => $avgRating,
                'rating_distribution' => $ratingPercentages,
                'total_reviews' => $totalReviews,
                'products_with_reviews' => count(array_filter($reviewCounts, function ($count) {
                    return $count > 0;
                }))
            ];
        } catch (\Exception $e) {
            Log::error('리뷰 및 평점 분석 중 오류 발생: ' . $e->getMessage(), [
                'competitor_data_count' => count($competitorData),
                'exception' => $e
            ]);
            
            return [
                'avg_rating' => 0,
                'rating_distribution' => [],
                'total_reviews' => 0,
                'error' => $e->getMessage()
            ];
        }
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
        $cacheKey = "user_competitor_thresholds_{$userId}";
        
        return Cache::remember($cacheKey, now()->addDays(7), function () {
            return $this->thresholds;
        });
    }
}
