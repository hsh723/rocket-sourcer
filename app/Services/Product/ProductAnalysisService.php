<?php

namespace RocketSourcer\Services\Product;

use RocketSourcer\Core\Cache;
use RocketSourcer\Core\Logger;
use RocketSourcer\Services\Crawler\ProductCrawlerService;
use RocketSourcer\Services\Coupang\CoupangProductService;
use Psr\Log\LoggerInterface;
use RocketSourcer\Models\Product;
use RocketSourcer\Models\Analysis;

class ProductAnalysisService
{
    private CoupangProductService $coupangProduct;
    private ProductCrawlerService $crawler;
    private CompetitorAnalysisService $competitor;
    private ProfitCalculatorService $profitCalc;
    private ProductRecommendationService $recommendation;
    private Cache $cache;
    private Logger $logger;
    private LoggerInterface $psrLogger;

    public function __construct(
        CoupangProductService $coupangProduct,
        ProductCrawlerService $crawler,
        CompetitorAnalysisService $competitor,
        ProfitCalculatorService $profitCalc,
        ProductRecommendationService $recommendation,
        Cache $cache,
        Logger $logger,
        LoggerInterface $psrLogger
    ) {
        $this->coupangProduct = $coupangProduct;
        $this->crawler = $crawler;
        $this->competitor = $competitor;
        $this->profitCalc = $profitCalc;
        $this->recommendation = $recommendation;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->psrLogger = $psrLogger;
    }

    /**
     * 제품 분석 실행
     */
    public function analyze(Product $product): Analysis
    {
        $cacheKey = "product_analysis:{$product->getId()}";
        
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }

        $analysis = new Analysis([
            'analyzable_type' => Product::class,
            'analyzable_id' => $product->getId(),
            'type' => 'product_analysis',
            'status' => 'processing',
        ]);

        try {
            // 제품 상세 정보 수집
            $productData = $this->coupangProduct->getProduct($product->getProductId());
            
            if (!$productData->isSuccess()) {
                throw new \Exception($productData->getMessage());
            }

            // 경쟁 제품 분석
            $competitors = $this->competitor->analyzeCompetitors($product);

            // 수익성 분석
            $profitAnalysis = $this->profitCalc->calculateProfit($product);

            // 크롤링 데이터 수집
            $crawledData = $this->crawler->collectProductData($product);

            // 분석 결과 저장
            $analysis->result = [
                'product_info' => [
                    'title' => $productData->getData()['title'],
                    'price' => $productData->getData()['price'],
                    'rating' => $productData->getData()['rating'],
                    'review_count' => $productData->getData()['review_count'],
                    'sales_estimate' => $this->estimateSales($productData->getData()),
                ],
                'market_analysis' => [
                    'market_size' => $this->calculateMarketSize($productData->getData()),
                    'growth_rate' => $this->calculateGrowthRate($productData->getData()),
                    'competition_level' => $competitors['competition_level'],
                ],
                'competitors' => $competitors['data'],
                'profit_analysis' => $profitAnalysis,
                'differentiation_points' => $this->analyzeDifferentiation($product, $competitors['data']),
                'trends' => $this->analyzeTrends($crawledData),
                'risks' => $this->assessRisks($product, $competitors['data']),
            ];

            $analysis->status = 'completed';
            $analysis->completed_at = now();

            // 캐시에 결과 저장
            $this->cache->set($cacheKey, $analysis, 3600);

            $this->logger->info('제품 분석 완료', [
                'product_id' => $product->getId(),
                'product_name' => $product->getName(),
            ]);

        } catch (\Exception $e) {
            $analysis->status = 'failed';
            $analysis->error = $e->getMessage();

            $this->logger->error('제품 분석 실패', [
                'product_id' => $product->getId(),
                'product_name' => $product->getName(),
                'error' => $e->getMessage(),
            ]);
        }

        $analysis->save();
        return $analysis;
    }

    /**
     * 판매량 추정
     */
    private function estimateSales(array $data): array
    {
        $reviewCount = $data['review_count'] ?? 0;
        $rating = $data['rating'] ?? 0;
        $price = $data['price'] ?? 0;

        // 리뷰당 평균 판매량 비율 (업종별로 다를 수 있음)
        $salesPerReview = 3.5;
        
        // 기본 판매량 추정
        $estimatedSales = $reviewCount * $salesPerReview;

        // 평점에 따른 보정
        if ($rating >= 4.5) {
            $estimatedSales *= 1.2;
        } elseif ($rating >= 4.0) {
            $estimatedSales *= 1.1;
        }

        return [
            'daily' => round($estimatedSales / 30),
            'monthly' => round($estimatedSales),
            'revenue' => round($estimatedSales * $price),
            'confidence_score' => $this->calculateConfidenceScore($reviewCount, $rating),
        ];
    }

    /**
     * 시장 규모 계산
     */
    private function calculateMarketSize(array $data): array
    {
        $categoryId = $data['category_id'] ?? null;
        $categoryData = $this->coupangProduct->getCategoryStats($categoryId);

        return [
            'total_products' => $categoryData->getData()['total_products'] ?? 0,
            'total_sellers' => $categoryData->getData()['total_sellers'] ?? 0,
            'estimated_revenue' => $categoryData->getData()['estimated_revenue'] ?? 0,
            'growth_rate' => $categoryData->getData()['growth_rate'] ?? 0,
        ];
    }

    /**
     * 성장률 계산
     */
    private function calculateGrowthRate(array $data): array
    {
        $historicalData = $data['historical_data'] ?? [];
        if (empty($historicalData)) {
            return [
                'monthly' => 0,
                'quarterly' => 0,
                'yearly' => 0,
            ];
        }

        return [
            'monthly' => $this->calculatePercentageChange(
                $historicalData['last_month'] ?? 0,
                $historicalData['current_month'] ?? 0
            ),
            'quarterly' => $this->calculatePercentageChange(
                $historicalData['last_quarter'] ?? 0,
                $historicalData['current_quarter'] ?? 0
            ),
            'yearly' => $this->calculatePercentageChange(
                $historicalData['last_year'] ?? 0,
                $historicalData['current_year'] ?? 0
            ),
        ];
    }

    /**
     * 차별화 포인트 분석
     */
    private function analyzeDifferentiation(Product $product, array $competitors): array
    {
        $points = [];

        // 가격 차별화
        $avgCompetitorPrice = array_sum(array_column($competitors, 'price')) / count($competitors);
        if ($product->getPrice() < $avgCompetitorPrice * 0.9) {
            $points[] = [
                'type' => 'price',
                'description' => '경쟁사 대비 저렴한 가격',
                'impact_score' => 8,
            ];
        }

        // 품질 차별화
        $avgCompetitorRating = array_sum(array_column($competitors, 'rating')) / count($competitors);
        if ($product->getRating() > $avgCompetitorRating + 0.5) {
            $points[] = [
                'type' => 'quality',
                'description' => '높은 고객 만족도',
                'impact_score' => 9,
            ];
        }

        // 기능 차별화
        $uniqueFeatures = array_diff(
            $product->getFeatures() ?? [],
            array_merge(...array_column($competitors, 'features'))
        );
        if (!empty($uniqueFeatures)) {
            $points[] = [
                'type' => 'features',
                'description' => '독특한 제품 기능',
                'features' => $uniqueFeatures,
                'impact_score' => 7,
            ];
        }

        return $points;
    }

    /**
     * 트렌드 분석
     */
    private function analyzeTrends(array $crawledData): array
    {
        return [
            'search_trends' => $crawledData['search_trends'] ?? [],
            'price_trends' => $crawledData['price_trends'] ?? [],
            'seasonal_patterns' => $this->analyzeSeasonality($crawledData['historical_data'] ?? []),
            'market_trends' => $crawledData['market_trends'] ?? [],
        ];
    }

    /**
     * 리스크 평가
     */
    private function assessRisks(Product $product, array $competitors): array
    {
        $risks = [];

        // 가격 경쟁 리스크
        if ($this->isPriceCompetitionHigh($competitors)) {
            $risks[] = [
                'type' => 'price_competition',
                'level' => 'high',
                'description' => '심각한 가격 경쟁이 예상됩니다.',
                'mitigation' => '차별화된 가치 제안이 필요합니다.',
            ];
        }

        // 시장 포화도 리스크
        if ($this->isMarketSaturated($competitors)) {
            $risks[] = [
                'type' => 'market_saturation',
                'level' => 'medium',
                'description' => '시장이 포화 상태입니다.',
                'mitigation' => '틈새 시장을 공략하세요.',
            ];
        }

        // 계절성 리스크
        $seasonality = $this->analyzeSeasonality($product->getHistoricalData());
        if ($seasonality['is_seasonal']) {
            $risks[] = [
                'type' => 'seasonality',
                'level' => 'medium',
                'description' => '계절적 변동이 큽니다.',
                'mitigation' => '재고 관리에 주의가 필요합니다.',
            ];
        }

        return $risks;
    }

    /**
     * 신뢰도 점수 계산
     */
    private function calculateConfidenceScore(int $reviewCount, float $rating): float
    {
        // 리뷰 수와 평점을 기반으로 신뢰도 점수 계산
        $reviewScore = min($reviewCount / 1000, 1) * 0.7;
        $ratingScore = ($rating / 5) * 0.3;
        
        return round(($reviewScore + $ratingScore) * 100, 2);
    }

    /**
     * 변화율 계산
     */
    private function calculatePercentageChange(float $oldValue, float $newValue): float
    {
        if ($oldValue == 0) {
            return $newValue > 0 ? 100 : 0;
        }

        return round((($newValue - $oldValue) / $oldValue) * 100, 2);
    }

    /**
     * 가격 경쟁 심각도 평가
     */
    private function isPriceCompetitionHigh(array $competitors): bool
    {
        $priceVariation = $this->calculatePriceVariation($competitors);
        return $priceVariation < 0.1; // 가격 변동이 10% 미만이면 심각한 가격 경쟁으로 판단
    }

    /**
     * 시장 포화도 평가
     */
    private function isMarketSaturated(array $competitors): bool
    {
        return count($competitors) > 50; // 경쟁사가 50개 이상이면 포화 상태로 판단
    }

    /**
     * 계절성 분석
     */
    private function analyzeSeasonality(array $historicalData): array
    {
        if (empty($historicalData)) {
            return [
                'is_seasonal' => false,
                'peak_months' => [],
                'low_months' => [],
            ];
        }

        $monthlyData = array_chunk($historicalData, 30);
        $monthlyAverages = array_map(function ($month) {
            return array_sum($month) / count($month);
        }, $monthlyData);

        $average = array_sum($monthlyAverages) / count($monthlyAverages);
        $peaks = [];
        $lows = [];

        foreach ($monthlyAverages as $month => $value) {
            if ($value > $average * 1.2) {
                $peaks[] = $month + 1;
            } elseif ($value < $average * 0.8) {
                $lows[] = $month + 1;
            }
        }

        return [
            'is_seasonal' => !empty($peaks) || !empty($lows),
            'peak_months' => $peaks,
            'low_months' => $lows,
        ];
    }
} 