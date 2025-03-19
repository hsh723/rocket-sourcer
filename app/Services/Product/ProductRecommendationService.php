<?php

namespace RocketSourcer\Services\Product;

use Psr\Log\LoggerInterface;
use RocketSourcer\Core\Cache;
use RocketSourcer\Models\Product;
use RocketSourcer\Services\Coupang\CoupangProductService;
use RocketSourcer\Services\Crawler\ProductCrawlerService;

class ProductRecommendationService
{
    protected CoupangProductService $coupangProductService;
    protected ProfitCalculatorService $profitCalculator;
    protected CompetitorAnalysisService $competitorAnalysis;
    protected ProductCrawlerService $crawler;
    protected Cache $cache;
    protected LoggerInterface $logger;

    public function __construct(
        CoupangProductService $coupangProductService,
        ProfitCalculatorService $profitCalculator,
        CompetitorAnalysisService $competitorAnalysis,
        ProductCrawlerService $crawler,
        Cache $cache,
        LoggerInterface $logger
    ) {
        $this->coupangProductService = $coupangProductService;
        $this->profitCalculator = $profitCalculator;
        $this->competitorAnalysis = $competitorAnalysis;
        $this->crawler = $crawler;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    /**
     * 제품 추천
     */
    public function getRecommendations(array $options = []): array
    {
        $cacheKey = "product_recommendations:" . md5(json_encode($options));
        
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }

        try {
            // 추천 제품 검색
            $products = $this->searchProducts($options);
            
            if (empty($products)) {
                return [
                    'recommendations' => [],
                    'metadata' => [
                        'total' => 0,
                        'filters' => $options,
                    ]
                ];
            }

            // 제품 분석 및 점수 계산
            $recommendations = $this->analyzeAndScoreProducts($products, $options);

            // 결과 정렬
            $recommendations = $this->sortRecommendations($recommendations, $options['sort_by'] ?? 'score');

            // 결과 제한
            $limit = $options['limit'] ?? 10;
            $recommendations = array_slice($recommendations, 0, $limit);

            $result = [
                'recommendations' => $recommendations,
                'metadata' => [
                    'total' => count($products),
                    'filtered' => count($recommendations),
                    'filters' => $options,
                ]
            ];

            // 캐시에 결과 저장
            $this->cache->set($cacheKey, $result, 3600);

            return $result;

        } catch (\Exception $e) {
            $this->logger->error('제품 추천 실패', [
                'options' => $options,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * 제품 검색
     */
    private function searchProducts(array $options): array
    {
        $searchResult = $this->coupangProductService->searchProducts([
            'category' => $options['category'] ?? null,
            'min_price' => $options['min_price'] ?? null,
            'max_price' => $options['max_price'] ?? null,
            'sort' => 'BEST_SELLING',
            'limit' => 100
        ]);

        if (!$searchResult->isSuccess()) {
            throw new \Exception($searchResult->getMessage());
        }

        return $searchResult->getData()['products'] ?? [];
    }

    /**
     * 제품 분석 및 점수 계산
     */
    private function analyzeAndScoreProducts(array $products, array $options): array
    {
        $recommendations = [];

        foreach ($products as $productData) {
            $product = new Product($productData);

            try {
                // 수익성 분석
                $profitAnalysis = $this->profitCalculator->calculateProfit($product);
                
                // 경쟁사 분석
                $competitorAnalysis = $this->competitorAnalysis->analyzeCompetitors($product);
                
                // 시장 데이터 수집
                $marketData = $this->crawler->collectMarketData($product->getCategory());

                // 점수 계산
                $scores = $this->calculateScores($product, $profitAnalysis, $competitorAnalysis, $marketData);

                // 필터링 조건 확인
                if ($this->passesFilters($scores, $options)) {
                    $recommendations[] = [
                        'product' => $product,
                        'scores' => $scores,
                        'analysis' => [
                            'profit' => $profitAnalysis,
                            'competition' => $competitorAnalysis,
                            'market' => $marketData,
                        ],
                        'total_score' => $this->calculateTotalScore($scores),
                    ];
                }

            } catch (\Exception $e) {
                $this->logger->warning('제품 분석 실패', [
                    'product_id' => $product->getId(),
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        return $recommendations;
    }

    /**
     * 점수 계산
     */
    private function calculateScores(
        Product $product,
        array $profitAnalysis,
        array $competitorAnalysis,
        array $marketData
    ): array {
        return [
            'profit' => $this->calculateProfitScore($profitAnalysis),
            'competition' => $this->calculateCompetitionScore($competitorAnalysis),
            'market_potential' => $this->calculateMarketPotentialScore($marketData),
            'quality' => $this->calculateQualityScore($product),
            'trend' => $this->calculateTrendScore($marketData),
            'risk' => $this->calculateRiskScore($competitorAnalysis, $marketData),
        ];
    }

    /**
     * 수익성 점수 계산
     */
    private function calculateProfitScore(array $profitAnalysis): float
    {
        $score = 0;

        // 순이익률 점수 (최대 40점)
        $netMarginRatio = $profitAnalysis['margins']['net_margin_ratio'] ?? 0;
        $score += min($netMarginRatio * 2, 40);

        // ROI 점수 (최대 30점)
        $roi = $profitAnalysis['margins']['roi'] ?? 0;
        $score += min($roi / 2, 30);

        // 손익분기점 도달 용이성 점수 (최대 30점)
        $breakevenUnits = $profitAnalysis['breakeven_analysis']['breakeven_units'] ?? 0;
        $score += max(0, 30 - ($breakevenUnits / 100));

        return min(100, $score);
    }

    /**
     * 경쟁 점수 계산
     */
    private function calculateCompetitionScore(array $competitorAnalysis): float
    {
        $score = 100;

        // 경쟁 수준에 따른 감점
        $competitionLevel = $competitorAnalysis['competition_level'] ?? 'UNKNOWN';
        switch ($competitionLevel) {
            case 'HIGH_CONCENTRATED':
                $score -= 50;
                break;
            case 'HIGH_FRAGMENTED':
                $score -= 30;
                break;
            case 'MEDIUM':
                $score -= 20;
                break;
            case 'LOW':
                $score -= 0;
                break;
        }

        // 시장 점유율 집중도에 따른 감점
        $concentration = $competitorAnalysis['market_share']['concentration'] ?? 0;
        $score -= min(30, $concentration / 2);

        return max(0, $score);
    }

    /**
     * 시장 잠재력 점수 계산
     */
    private function calculateMarketPotentialScore(array $marketData): float
    {
        $score = 0;

        // 시장 규모 점수 (최대 40점)
        $marketSize = $marketData['market_size'] ?? 0;
        $score += min($marketSize / 1000000, 40);

        // 성장률 점수 (최대 40점)
        $growthRate = $marketData['growth_rate'] ?? 0;
        $score += min($growthRate * 2, 40);

        // 진입 장벽 점수 (최대 20점)
        $entryBarrier = $marketData['entry_barrier'] ?? 'MEDIUM';
        switch ($entryBarrier) {
            case 'LOW':
                $score += 20;
                break;
            case 'MEDIUM':
                $score += 10;
                break;
            case 'HIGH':
                $score += 0;
                break;
        }

        return min(100, $score);
    }

    /**
     * 품질 점수 계산
     */
    private function calculateQualityScore(Product $product): float
    {
        $score = 0;

        // 평점 점수 (최대 50점)
        $rating = $product->getRating();
        $score += $rating * 10;

        // 리뷰 수 점수 (최대 30점)
        $reviewCount = $product->getReviewCount();
        $score += min($reviewCount / 100, 30);

        // 판매자 신뢰도 점수 (최대 20점)
        $sellerRating = $product->getSellerRating();
        $score += $sellerRating * 4;

        return min(100, $score);
    }

    /**
     * 트렌드 점수 계산
     */
    private function calculateTrendScore(array $marketData): float
    {
        $score = 50; // 기본 점수

        // 검색량 트렌드 반영
        $searchTrend = $marketData['search_trends']['growth_rate'] ?? 0;
        $score += min($searchTrend * 2, 25);

        // 계절성 영향 반영
        $seasonality = $marketData['seasonality'] ?? [];
        if (!empty($seasonality)) {
            $currentMonth = (int)date('n');
            $monthScore = $seasonality[$currentMonth] ?? 1;
            $score += $monthScore * 25;
        }

        return min(100, $score);
    }

    /**
     * 리스크 점수 계산
     */
    private function calculateRiskScore(array $competitorAnalysis, array $marketData): float
    {
        $score = 100; // 시작 점수

        // 경쟁 리스크
        $competitionLevel = $competitorAnalysis['competition_level'] ?? 'UNKNOWN';
        switch ($competitionLevel) {
            case 'HIGH_CONCENTRATED':
                $score -= 40;
                break;
            case 'HIGH_FRAGMENTED':
                $score -= 30;
                break;
            case 'MEDIUM':
                $score -= 20;
                break;
        }

        // 시장 리스크
        $marketRisks = $marketData['risks'] ?? [];
        foreach ($marketRisks as $risk) {
            switch ($risk['level']) {
                case 'HIGH':
                    $score -= 20;
                    break;
                case 'MEDIUM':
                    $score -= 10;
                    break;
                case 'LOW':
                    $score -= 5;
                    break;
            }
        }

        return max(0, $score);
    }

    /**
     * 총점 계산
     */
    private function calculateTotalScore(array $scores): float
    {
        $weights = [
            'profit' => 0.3,
            'competition' => 0.2,
            'market_potential' => 0.2,
            'quality' => 0.1,
            'trend' => 0.1,
            'risk' => 0.1,
        ];

        $totalScore = 0;
        foreach ($weights as $metric => $weight) {
            $totalScore += ($scores[$metric] ?? 0) * $weight;
        }

        return round($totalScore, 2);
    }

    /**
     * 필터링 조건 확인
     */
    private function passesFilters(array $scores, array $options): bool
    {
        // 최소 마진 필터
        if (isset($options['min_margin'])) {
            if (($scores['profit'] ?? 0) < $options['min_margin']) {
                return false;
            }
        }

        // 최소 품질 점수 필터
        if (isset($options['min_quality'])) {
            if (($scores['quality'] ?? 0) < $options['min_quality']) {
                return false;
            }
        }

        // 최대 리스크 점수 필터
        if (isset($options['max_risk'])) {
            if (($scores['risk'] ?? 0) > $options['max_risk']) {
                return false;
            }
        }

        return true;
    }

    /**
     * 추천 결과 정렬
     */
    private function sortRecommendations(array $recommendations, string $sortBy): array
    {
        switch ($sortBy) {
            case 'profit':
                usort($recommendations, fn($a, $b) => 
                    $b['scores']['profit'] <=> $a['scores']['profit']
                );
                break;
            
            case 'market_potential':
                usort($recommendations, fn($a, $b) => 
                    $b['scores']['market_potential'] <=> $a['scores']['market_potential']
                );
                break;
            
            case 'quality':
                usort($recommendations, fn($a, $b) => 
                    $b['scores']['quality'] <=> $a['scores']['quality']
                );
                break;
            
            case 'risk':
                usort($recommendations, fn($a, $b) => 
                    $a['scores']['risk'] <=> $b['scores']['risk']
                );
                break;
            
            case 'score':
            default:
                usort($recommendations, fn($a, $b) => 
                    $b['total_score'] <=> $a['total_score']
                );
                break;
        }

        return $recommendations;
    }
} 