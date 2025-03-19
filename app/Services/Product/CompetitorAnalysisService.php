<?php

namespace RocketSourcer\Services\Product;

use Psr\Log\LoggerInterface;
use RocketSourcer\Core\Cache;
use RocketSourcer\Models\Product;
use RocketSourcer\Services\Coupang\CoupangProductService;
use RocketSourcer\Services\Crawler\ProductCrawlerService;

class CompetitorAnalysisService
{
    protected CoupangProductService $coupangProductService;
    protected Cache $cache;
    protected LoggerInterface $logger;
    protected ProductCrawlerService $crawler;

    public function __construct(
        CoupangProductService $coupangProductService,
        Cache $cache,
        LoggerInterface $logger,
        ProductCrawlerService $crawler
    ) {
        $this->coupangProductService = $coupangProductService;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->crawler = $crawler;
    }

    /**
     * 경쟁 제품 분석
     */
    public function analyzeCompetitors(Product $product): array
    {
        $cacheKey = "competitor_analysis:{$product->getId()}";
        
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }

        try {
            // 경쟁 제품 데이터 수집
            $competitors = $this->coupangProductService->getCompetitors($product->getProductId(), [
                'limit' => 20,
                'sort' => 'RELEVANCE'
            ]);

            if (!$competitors->isSuccess()) {
                throw new \Exception($competitors->getMessage());
            }

            // 크롤링을 통한 추가 데이터 수집
            $crawledData = $this->crawler->collectCompetitorData($product->getProductId());

            // 경쟁사 데이터 분석
            $competitorData = $this->processCompetitors($competitors->getData()['competitors'] ?? []);
            
            // 시장 점유율 분석
            $marketShare = $this->analyzeMarketShare($competitorData);
            
            // 가격 분석
            $priceAnalysis = $this->analyzePricing($product, $competitorData);
            
            // 품질 분석
            $qualityAnalysis = $this->analyzeQuality($product, $competitorData);

            $result = [
                'competition_level' => $this->determineCompetitionLevel($competitorData),
                'market_share' => $marketShare,
                'price_analysis' => $priceAnalysis,
                'quality_analysis' => $qualityAnalysis,
                'competitors' => $competitorData,
                'strengths_weaknesses' => $this->analyzeStrengthsWeaknesses($product, $competitorData),
                'opportunities_threats' => $this->analyzeOpportunitiesThreats($product, $competitorData),
                'metadata' => [
                    'analyzed_at' => date('Y-m-d H:i:s'),
                    'data_sources' => [
                        'api' => true,
                        'crawler' => !empty($crawledData)
                    ]
                ]
            ];

            // 캐시에 결과 저장
            $this->cache->set($cacheKey, $result, 3600);

            return $result;

        } catch (\Exception $e) {
            $this->logger->error('경쟁 제품 분석 실패', [
                'product_id' => $product->getId(),
                'product_name' => $product->getName(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * 경쟁사 데이터 처리
     */
    private function processCompetitors(array $competitors): array
    {
        return array_map(function ($competitor) {
            return [
                'product_id' => $competitor['product_id'],
                'title' => $competitor['title'],
                'price' => $competitor['price'],
                'rating' => $competitor['rating'],
                'review_count' => $competitor['review_count'],
                'seller' => [
                    'id' => $competitor['seller']['id'],
                    'name' => $competitor['seller']['name'],
                    'rating' => $competitor['seller']['rating'],
                ],
                'sales_metrics' => [
                    'sales_volume' => $competitor['sales_volume'] ?? 0,
                    'revenue' => $competitor['revenue'] ?? 0,
                    'market_share' => $competitor['market_share'] ?? 0,
                ],
                'product_metrics' => [
                    'price_competitiveness' => $competitor['price_competitiveness'] ?? 0,
                    'quality_score' => $competitor['quality_score'] ?? 0,
                    'customer_satisfaction' => $competitor['customer_satisfaction'] ?? 0,
                ],
            ];
        }, $competitors);
    }

    /**
     * 시장 점유율 분석
     */
    private function analyzeMarketShare(array $competitors): array
    {
        $totalSales = array_sum(array_column(array_column($competitors, 'sales_metrics'), 'sales_volume'));
        if ($totalSales === 0) {
            return [
                'distribution' => [],
                'concentration' => 0,
                'leader_share' => 0,
            ];
        }

        $shares = [];
        foreach ($competitors as $competitor) {
            $sales = $competitor['sales_metrics']['sales_volume'];
            $shares[$competitor['product_id']] = [
                'product_id' => $competitor['product_id'],
                'name' => $competitor['title'],
                'share' => round(($sales / $totalSales) * 100, 2),
            ];
        }

        // 점유율로 정렬
        usort($shares, function ($a, $b) {
            return $b['share'] <=> $a['share'];
        });

        return [
            'distribution' => $shares,
            'concentration' => $this->calculateMarketConcentration($shares),
            'leader_share' => $shares[0]['share'] ?? 0,
        ];
    }

    /**
     * 가격 분석
     */
    private function analyzePricing(Product $product, array $competitors): array
    {
        $prices = array_column($competitors, 'price');
        if (empty($prices)) {
            return [
                'average_price' => 0,
                'price_range' => ['min' => 0, 'max' => 0],
                'position' => 'unknown',
                'distribution' => [],
            ];
        }

        $averagePrice = array_sum($prices) / count($prices);
        $productPrice = $product->getPrice();

        // 가격 분포 계산
        $distribution = $this->calculatePriceDistribution($prices);

        // 가격 포지션 결정
        $position = $this->determinePricePosition($productPrice, $prices);

        return [
            'average_price' => round($averagePrice, 2),
            'price_range' => [
                'min' => min($prices),
                'max' => max($prices),
            ],
            'position' => $position,
            'distribution' => $distribution,
            'price_competitiveness' => $this->calculatePriceCompetitiveness($productPrice, $prices),
        ];
    }

    /**
     * 품질 분석
     */
    private function analyzeQuality(Product $product, array $competitors): array
    {
        $ratings = array_column($competitors, 'rating');
        $reviewCounts = array_column($competitors, 'review_count');

        if (empty($ratings)) {
            return [
                'average_rating' => 0,
                'rating_distribution' => [],
                'position' => 'unknown',
                'quality_score' => 0,
            ];
        }

        $averageRating = array_sum($ratings) / count($ratings);
        $productRating = $product->getRating();

        return [
            'average_rating' => round($averageRating, 2),
            'rating_distribution' => $this->calculateRatingDistribution($ratings),
            'position' => $this->determineQualityPosition($productRating, $ratings),
            'quality_score' => $this->calculateQualityScore($product, $ratings, $reviewCounts),
        ];
    }

    /**
     * 강점과 약점 분석
     */
    private function analyzeStrengthsWeaknesses(Product $product, array $competitors): array
    {
        $strengths = [];
        $weaknesses = [];

        // 가격 경쟁력 분석
        $priceAnalysis = $this->analyzePricing($product, $competitors);
        if ($priceAnalysis['position'] === 'low') {
            $strengths[] = [
                'type' => 'price',
                'description' => '경쟁사 대비 가격 경쟁력이 높습니다.',
                'impact_score' => 8,
            ];
        } elseif ($priceAnalysis['position'] === 'high') {
            $weaknesses[] = [
                'type' => 'price',
                'description' => '가격이 경쟁사 대비 높습니다.',
                'impact_score' => 7,
            ];
        }

        // 품질 경쟁력 분석
        $qualityAnalysis = $this->analyzeQuality($product, $competitors);
        if ($qualityAnalysis['position'] === 'high') {
            $strengths[] = [
                'type' => 'quality',
                'description' => '제품 품질이 우수합니다.',
                'impact_score' => 9,
            ];
        } elseif ($qualityAnalysis['position'] === 'low') {
            $weaknesses[] = [
                'type' => 'quality',
                'description' => '품질 개선이 필요합니다.',
                'impact_score' => 8,
            ];
        }

        return [
            'strengths' => $strengths,
            'weaknesses' => $weaknesses,
        ];
    }

    /**
     * 기회와 위협 분석
     */
    private function analyzeOpportunitiesThreats(Product $product, array $competitors): array
    {
        $opportunities = [];
        $threats = [];

        // 시장 점유율 분석
        $marketShare = $this->analyzeMarketShare($competitors);
        if ($marketShare['concentration'] < 40) {
            $opportunities[] = [
                'type' => 'market_share',
                'description' => '시장이 분산되어 있어 점유율 확대 기회가 있습니다.',
                'potential_score' => 8,
            ];
        } elseif ($marketShare['concentration'] > 70) {
            $threats[] = [
                'type' => 'market_dominance',
                'description' => '시장이 소수 업체에 의해 지배되고 있습니다.',
                'risk_score' => 7,
            ];
        }

        // 가격 경쟁 분석
        $priceAnalysis = $this->analyzePricing($product, $competitors);
        if ($priceAnalysis['price_competitiveness'] > 0.7) {
            $opportunities[] = [
                'type' => 'pricing',
                'description' => '가격 프리미엄을 통한 수익성 개선 기회가 있습니다.',
                'potential_score' => 7,
            ];
        } elseif ($priceAnalysis['price_competitiveness'] < 0.3) {
            $threats[] = [
                'type' => 'price_competition',
                'description' => '심각한 가격 경쟁이 예상됩니다.',
                'risk_score' => 8,
            ];
        }

        return [
            'opportunities' => $opportunities,
            'threats' => $threats,
        ];
    }

    /**
     * 시장 집중도 계산
     */
    private function calculateMarketConcentration(array $shares): float
    {
        // 상위 3개 기업의 시장 점유율 합
        $topShares = array_slice(array_column($shares, 'share'), 0, 3);
        return array_sum($topShares);
    }

    /**
     * 가격 분포 계산
     */
    private function calculatePriceDistribution(array $prices): array
    {
        $min = min($prices);
        $max = max($prices);
        $range = $max - $min;
        
        if ($range === 0) {
            return [
                'low' => count($prices),
                'medium' => 0,
                'high' => 0,
            ];
        }

        $distribution = [
            'low' => 0,
            'medium' => 0,
            'high' => 0,
        ];

        foreach ($prices as $price) {
            $position = ($price - $min) / $range;
            if ($position < 0.33) {
                $distribution['low']++;
            } elseif ($position < 0.66) {
                $distribution['medium']++;
            } else {
                $distribution['high']++;
            }
        }

        return $distribution;
    }

    /**
     * 가격 포지션 결정
     */
    private function determinePricePosition(float $price, array $competitorPrices): string
    {
        $average = array_sum($competitorPrices) / count($competitorPrices);
        $difference = ($price - $average) / $average;

        if ($difference < -0.1) return 'low';
        if ($difference > 0.1) return 'high';
        return 'medium';
    }

    /**
     * 가격 경쟁력 계산
     */
    private function calculatePriceCompetitiveness(float $price, array $competitorPrices): float
    {
        $average = array_sum($competitorPrices) / count($competitorPrices);
        if ($average === 0) return 0;

        $competitiveness = 1 - (($price - min($competitorPrices)) / ($average - min($competitorPrices)));
        return max(0, min(1, $competitiveness));
    }

    /**
     * 평점 분포 계산
     */
    private function calculateRatingDistribution(array $ratings): array
    {
        $distribution = [
            '5' => 0, '4' => 0, '3' => 0, '2' => 0, '1' => 0
        ];

        foreach ($ratings as $rating) {
            $key = floor($rating);
            if (isset($distribution["$key"])) {
                $distribution["$key"]++;
            }
        }

        return $distribution;
    }

    /**
     * 품질 포지션 결정
     */
    private function determineQualityPosition(float $rating, array $competitorRatings): string
    {
        $average = array_sum($competitorRatings) / count($competitorRatings);
        $difference = $rating - $average;

        if ($difference < -0.3) return 'low';
        if ($difference > 0.3) return 'high';
        return 'medium';
    }

    /**
     * 품질 점수 계산
     */
    private function calculateQualityScore(Product $product, array $ratings, array $reviewCounts): float
    {
        $averageRating = array_sum($ratings) / count($ratings);
        $averageReviews = array_sum($reviewCounts) / count($reviewCounts);

        $ratingScore = ($product->getRating() / $averageRating) * 0.7;
        $reviewScore = (min($product->getReviewCount(), $averageReviews * 2) / ($averageReviews * 2)) * 0.3;

        return round(($ratingScore + $reviewScore) * 100, 2);
    }

    /**
     * 경쟁 수준 결정
     */
    private function determineCompetitionLevel(array $competitors): string
    {
        $count = count($competitors);
        $concentration = $this->calculateMarketConcentration(
            $this->analyzeMarketShare($competitors)['distribution']
        );

        if ($count < 5) return 'LOW';
        if ($count < 15) return 'MEDIUM';
        if ($concentration > 70) return 'HIGH_CONCENTRATED';
        return 'HIGH_FRAGMENTED';
    }
} 