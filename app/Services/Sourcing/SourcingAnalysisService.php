<?php

namespace App\Services\Sourcing;

use App\Services\Crawler\CoupangCrawlerService;
use App\Services\Crawler\DomeggookCrawlerService;
use App\Models\CoupangProduct;
use App\Models\DomeggookProduct;
use App\Models\ProductMatch;
use App\Models\SourcingAnalysis;
use App\Models\ProductRecommendation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class SourcingAnalysisService
{
    protected $coupangCrawler;
    protected $domeggookCrawler;
    protected $cacheExpiration = 3600; // 1시간

    public function __construct(CoupangCrawlerService $coupangCrawler, DomeggookCrawlerService $domeggookCrawler)
    {
        $this->coupangCrawler = $coupangCrawler;
        $this->domeggookCrawler = $domeggookCrawler;
    }

    /**
     * 제품 소싱 분석
     *
     * @param string $coupangProductId 쿠팡 제품 ID
     * @param array $options 분석 옵션
     * @return array
     */
    public function analyzeProduct(string $coupangProductId, array $options = []): array
    {
        $cacheKey = "sourcing_analysis_{$coupangProductId}_" . md5(json_encode($options));
        
        return Cache::remember($cacheKey, $this->cacheExpiration, function () use ($coupangProductId, $options) {
            try {
                // 쿠팡 제품 정보 조회
                $coupangProduct = $this->getCoupangProduct($coupangProductId);
                
                if (empty($coupangProduct)) {
                    return [
                        'success' => false,
                        'message' => '쿠팡 제품을 찾을 수 없습니다',
                        'data' => []
                    ];
                }
                
                // 도매꾹 매칭 제품 검색
                $matches = $this->findMatchingProducts($coupangProduct, $options['limit'] ?? 10);
                
                if (empty($matches)) {
                    return [
                        'success' => false,
                        'message' => '매칭되는 도매꾹 제품을 찾을 수 없습니다',
                        'data' => [
                            'coupang_product' => $coupangProduct,
                            'matches' => []
                        ]
                    ];
                }
                
                // 수익성 분석
                $profitabilityAnalysis = $this->analyzeProfitability($coupangProduct, $matches, $options);
                
                // 분석 결과 저장
                $analysisId = $this->saveAnalysisResult($coupangProduct, $matches, $profitabilityAnalysis);
                
                return [
                    'success' => true,
                    'data' => [
                        'analysis_id' => $analysisId,
                        'coupang_product' => $coupangProduct,
                        'matches' => $matches,
                        'profitability' => $profitabilityAnalysis
                    ]
                ];
            } catch (\Exception $e) {
                Log::error('제품 소싱 분석 오류: ' . $e->getMessage());
                return [
                    'success' => false,
                    'message' => '분석 중 오류가 발생했습니다: ' . $e->getMessage(),
                    'data' => []
                ];
            }
        });
    }

    /**
     * 마진율 계산
     *
     * @param array $retailProduct 소매 제품 정보
     * @param array $wholesaleProduct 도매 제품 정보
     * @param array $options 계산 옵션
     * @return array
     */
    public function calculateMargin(array $retailProduct, array $wholesaleProduct, array $options = []): array
    {
        // 기본 비용 설정
        $retailPrice = $retailProduct['price'] ?? 0;
        $wholesalePrice = $wholesaleProduct['price'] ?? 0;
        $minOrderQuantity = $wholesaleProduct['min_order_quantity'] ?? 1;
        
        // 추가 비용 설정
        $shippingCost = $options['shipping_cost'] ?? $wholesaleProduct['shipping_fee'] ?? 0;
        $marketplaceFeeRate = $options['marketplace_fee_rate'] ?? 0.1; // 기본 10%
        $marketingCostRate = $options['marketing_cost_rate'] ?? 0.05; // 기본 5%
        $additionalCosts = $options['additional_costs'] ?? 0;
        
        // 총 비용 계산
        $totalCostPerUnit = ($wholesalePrice * $minOrderQuantity + $shippingCost) / $minOrderQuantity;
        $marketplaceFee = $retailPrice * $marketplaceFeeRate;
        $marketingCost = $retailPrice * $marketingCostRate;
        $totalCost = $totalCostPerUnit + $marketplaceFee + $marketingCost + $additionalCosts;
        
        // 마진 계산
        $profit = $retailPrice - $totalCost;
        $marginAmount = $profit;
        $marginRate = ($profit / $retailPrice) * 100;
        $roi = ($profit / $totalCost) * 100;
        
        // 손익분기점 계산
        $fixedCosts = $options['fixed_costs'] ?? 0;
        $breakEvenUnits = $fixedCosts > 0 ? ceil($fixedCosts / $profit) : 0;
        $breakEvenRevenue = $breakEvenUnits * $retailPrice;
        
        return [
            'retail_price' => $retailPrice,
            'wholesale_price' => $wholesalePrice,
            'min_order_quantity' => $minOrderQuantity,
            'costs' => [
                'product_cost' => $wholesalePrice,
                'shipping_cost' => $shippingCost,
                'marketplace_fee' => $marketplaceFee,
                'marketing_cost' => $marketingCost,
                'additional_costs' => $additionalCosts,
                'total_cost_per_unit' => $totalCost
            ],
            'margin' => [
                'amount' => round($marginAmount, 2),
                'rate' => round($marginRate, 2),
                'roi' => round($roi, 2)
            ],
            'break_even' => [
                'units' => $breakEvenUnits,
                'revenue' => $breakEvenRevenue
            ],
            'profitability_score' => $this->calculateProfitabilityScore($marginRate, $roi)
        ];
    }

    /**
     * 수익성 점수 계산
     *
     * @param float $marginRate 마진율
     * @param float $roi ROI
     * @return int
     */
    protected function calculateProfitabilityScore(float $marginRate, float $roi): int
    {
        // 마진율과 ROI를 기반으로 0-100 점수 계산
        $marginScore = min(max($marginRate, 0), 50) * 1.5; // 최대 75점
        $roiScore = min(max($roi, 0), 100) * 0.25; // 최대 25점
        
        return min(round($marginScore + $roiScore), 100);
    }

    /**
     * 수익성 분석
     *
     * @param array $coupangProduct 쿠팡 제품 정보
     * @param array $matches 매칭된 도매꾹 제품 목록
     * @param array $options 분석 옵션
     * @return array
     */
    protected function analyzeProfitability(array $coupangProduct, array $matches, array $options = []): array
    {
        $results = [];
        $bestMatch = null;
        $highestMargin = -1;
        
        foreach ($matches as $match) {
            $margin = $this->calculateMargin($coupangProduct, $match, $options);
            $match['margin'] = $margin;
            $results[] = $match;
            
            // 최고 마진 제품 찾기
            if ($margin['margin']['amount'] > $highestMargin) {
                $highestMargin = $margin['margin']['amount'];
                $bestMatch = $match;
            }
        }
        
        // 월간 예상 판매량 및 수익 계산
        $monthlySales = $options['monthly_sales'] ?? $this->estimateMonthlySales($coupangProduct);
        $monthlyProfit = $highestMargin * $monthlySales;
        
        // 투자 회수 기간 계산
        $initialInvestment = $bestMatch['price'] * ($bestMatch['min_order_quantity'] ?? 1);
        $recoupmentPeriod = $monthlyProfit > 0 ? $initialInvestment / $monthlyProfit : 0;
        
        return [
            'results' => $results,
            'best_match' => $bestMatch,
            'monthly_sales' => $monthlySales,
            'monthly_profit' => round($monthlyProfit, 2),
            'initial_investment' => $initialInvestment,
            'recoupment_period' => round($recoupmentPeriod, 2), // 개월 단위
            'recommendation' => $this->generateRecommendation($bestMatch['margin'] ?? [], $monthlySales, $recoupmentPeriod)
        ];
    }

    /**
     * 월간 판매량 추정
     *
     * @param array $product 제품 정보
     * @return int
     */
    protected function estimateMonthlySales(array $product): int
    {
        // 리뷰 수를 기반으로 판매량 추정 (리뷰 작성률 약 3% 가정)
        $reviewCount = $product['review_count'] ?? 0;
        $estimatedTotalSales = $reviewCount / 0.03;
        
        // 제품 등록 기간 (개월) 계산
        $registrationDate = isset($product['registration_date']) 
            ? Carbon::parse($product['registration_date']) 
            : Carbon::now()->subMonths(6); // 기본값 6개월
        $monthsSinceRegistration = max(Carbon::now()->diffInMonths($registrationDate), 1);
        
        // 월간 판매량 계산
        $monthlySales = ceil($estimatedTotalSales / $monthsSinceRegistration);
        
        // 판매량 범위 제한 (너무 극단적인 값 방지)
        return max(min($monthlySales, 1000), 5);
    }

    /**
     * 추천 생성
     *
     * @param array $margin 마진 정보
     * @param int $monthlySales 월간 판매량
     * @param float $recoupmentPeriod 투자 회수 기간
     * @return string
     */
    protected function generateRecommendation(array $margin, int $monthlySales, float $recoupmentPeriod): string
    {
        $marginRate = $margin['rate'] ?? 0;
        $roi = $margin['roi'] ?? 0;
        
        if ($marginRate >= 30 && $roi >= 50 && $recoupmentPeriod <= 2) {
            return '매우 추천';
        } elseif ($marginRate >= 20 && $roi >= 30 && $recoupmentPeriod <= 3) {
            return '추천';
        } elseif ($marginRate >= 15 && $roi >= 20 && $recoupmentPeriod <= 4) {
            return '고려 가능';
        } elseif ($marginRate >= 10 && $roi >= 15) {
            return '신중 검토';
        } else {
            return '비추천';
        }
    }

    /**
     * 쿠팡 제품 정보 조회
     *
     * @param string $productId 쿠팡 제품 ID
     * @return array|null
     */
    protected function getCoupangProduct(string $productId): ?array
    {
        // DB에서 조회
        $product = CoupangProduct::where('product_id', $productId)->first();
        
        if ($product) {
            return $product->toArray();
        }
        
        // API에서 조회
        $result = $this->coupangCrawler->getProductDetails($productId);
        
        if ($result['success'] && !empty($result['data'])) {
            return $result['data'];
        }
        
        return null;
    }

    /**
     * 매칭 제품 검색
     *
     * @param array $coupangProduct 쿠팡 제품 정보
     * @param int $limit 조회할 제품 수
     * @return array
     */
    protected function findMatchingProducts(array $coupangProduct, int $limit = 10): array
    {
        // 기존 매칭 결과 조회
        $existingMatches = ProductMatch::where('source_product_id', $coupangProduct['id'] ?? $coupangProduct['productId'])
            ->where('platform', 'domeggook')
            ->orderBy('rank')
            ->limit($limit)
            ->get();
        
        if ($existingMatches->count() > 0) {
            return $existingMatches->map(function ($match) {
                $data = json_decode($match->data, true);
                $data['similarity'] = $match->similarity;
                $data['rank'] = $match->rank;
                return $data;
            })->toArray();
        }
        
        // 새로 매칭 검색
        $result = $this->domeggookCrawler->findProductMatches($coupangProduct, $limit);
        
        if ($result['success'] && !empty($result['data']['matches'])) {
            return $result['data']['matches'];
        }
        
        return [];
    }

    /**
     * 분석 결과 저장
     *
     * @param array $coupangProduct 쿠팡 제품 정보
     * @param array $matches 매칭된 도매꾹 제품 목록
     * @param array $profitabilityAnalysis 수익성 분석 결과
     * @return int 분석 ID
     */
    protected function saveAnalysisResult(array $coupangProduct, array $matches, array $profitabilityAnalysis): int
    {
        try {
            $analysis = SourcingAnalysis::create([
                'coupang_product_id' => $coupangProduct['id'] ?? $coupangProduct['productId'],
                'best_match_id' => $profitabilityAnalysis['best_match']['productId'] ?? null,
                'margin_rate' => $profitabilityAnalysis['best_match']['margin']['rate'] ?? 0,
                'roi' => $profitabilityAnalysis['best_match']['margin']['roi'] ?? 0,
                'monthly_profit' => $profitabilityAnalysis['monthly_profit'] ?? 0,
                'recoupment_period' => $profitabilityAnalysis['recoupment_period'] ?? 0,
                'recommendation' => $profitabilityAnalysis['recommendation'] ?? '',
                'data' => json_encode([
                    'coupang_product' => $coupangProduct,
                    'matches' => $matches,
                    'profitability' => $profitabilityAnalysis
                ]),
                'created_at' => Carbon::now()
            ]);
            
            return $analysis->id;
        } catch (\Exception $e) {
            Log::error('분석 결과 저장 오류: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * 제품 추천
     *
     * @param array $options 추천 옵션
     * @return array
     */
    public function recommendProducts(array $options = []): array
    {
        $cacheKey = "product_recommendations_" . md5(json_encode($options));
        
        return Cache::remember($cacheKey, $this->cacheExpiration, function () use ($options) {
            try {
                // 추천 기준 설정
                $minMarginRate = $options['min_margin_rate'] ?? 20;
                $minRoi = $options['min_roi'] ?? 30;
                $maxRecoupmentPeriod = $options['max_recoupment_period'] ?? 3;
                $limit = $options['limit'] ?? 20;
                $category = $options['category'] ?? null;
                
                // 인기 제품 조회
                $popularProducts = $this->getPopularProducts($category, $limit * 2);
                
                // 각 제품에 대한 소싱 분석
                $recommendations = [];
                
                foreach ($popularProducts as $product) {
                    $analysis = $this->analyzeProduct($product['productId'], [
                        'limit' => 5,
                        'monthly_sales' => $product['estimated_monthly_sales'] ?? null
                    ]);
                    
                    if ($analysis['success']) {
                        $profitability = $analysis['data']['profitability'] ?? [];
                        $bestMatch = $profitability['best_match'] ?? [];
                        $margin = $bestMatch['margin'] ?? [];
                        
                        // 추천 기준 충족 여부 확인
                        if (
                            ($margin['rate'] ?? 0) >= $minMarginRate &&
                            ($margin['roi'] ?? 0) >= $minRoi &&
                            ($profitability['recoupment_period'] ?? 999) <= $maxRecoupmentPeriod
                        ) {
                            $recommendations[] = [
                                'coupang_product' => $product,
                                'best_match' => $bestMatch,
                                'profitability' => [
                                    'margin_rate' => $margin['rate'] ?? 0,
                                    'roi' => $margin['roi'] ?? 0,
                                    'monthly_profit' => $profitability['monthly_profit'] ?? 0,
                                    'recoupment_period' => $profitability['recoupment_period'] ?? 0
                                ],
                                'score' => $this->calculateRecommendationScore($margin, $profitability)
                            ];
                        }
                    }
                    
                    // 충분한 추천 제품을 찾았으면 중단
                    if (count($recommendations) >= $limit) {
                        break;
                    }
                }
                
                // 점수 기준으로 정렬
                usort($recommendations, function ($a, $b) {
                    return $b['score'] <=> $a['score'];
                });
                
                // 추천 결과 저장
                $this->saveRecommendations($recommendations);
                
                return [
                    'success' => true,
                    'data' => [
                        'recommendations' => array_slice($recommendations, 0, $limit),
                        'total' => count($recommendations),
                        'criteria' => [
                            'min_margin_rate' => $minMarginRate,
                            'min_roi' => $minRoi,
                            'max_recoupment_period' => $maxRecoupmentPeriod
                        ]
                    ]
                ];
            } catch (\Exception $e) {
                Log::error('제품 추천 오류: ' . $e->getMessage());
                return [
                    'success' => false,
                    'message' => '추천 중 오류가 발생했습니다: ' . $e->getMessage(),
                    'data' => []
                ];
            }
        });
    }

    /**
     * 인기 제품 조회
     *
     * @param string|null $category 카테고리
     * @param int $limit 조회할 제품 수
     * @return array
     */
    protected function getPopularProducts(?string $category, int $limit = 100): array
    {
        if ($category) {
            $result = $this->coupangCrawler->getBestSellers($category, $limit);
        } else {
            // 여러 카테고리에서 인기 제품 조회
            $popularCategories = [
                '1001', // 패션의류
                '1002', // 패션잡화
                '1010', // 가전디지털
                '1011', // 가구/인테리어
                '1012'  // 식품
            ];
            
            $result = ['success' => true, 'data' => ['products' => []]];
            
            foreach ($popularCategories as $categoryId) {
                $categoryResult = $this->coupangCrawler->getBestSellers($categoryId, ceil($limit / count($popularCategories)));
                
                if ($categoryResult['success']) {
                    $result['data']['products'] = array_merge(
                        $result['data']['products'],
                        $categoryResult['data']['products'] ?? []
                    );
                }
            }
        }
        
        if ($result['success'] && !empty($result['data']['products'])) {
            $products = $result['data']['products'];
            
            // 각 제품에 예상 월간 판매량 추가
            foreach ($products as &$product) {
                $product['estimated_monthly_sales'] = $this->estimateMonthlySales($product);
            }
            
            return $products;
        }
        
        return [];
    }

    /**
     * 추천 점수 계산
     *
     * @param array $margin 마진 정보
     * @param array $profitability 수익성 정보
     * @return int
     */
    protected function calculateRecommendationScore(array $margin, array $profitability): int
    {
        $marginRate = $margin['rate'] ?? 0;
        $roi = $margin['roi'] ?? 0;
        $monthlyProfit = $profitability['monthly_profit'] ?? 0;
        $recoupmentPeriod = $profitability['recoupment_period'] ?? 999;
        
        // 마진율 점수 (최대 30점)
        $marginScore = min($marginRate, 50) * 0.6;
        
        // ROI 점수 (최대 20점)
        $roiScore = min($roi, 100) * 0.2;
        
        // 월 수익 점수 (최대 30점)
        $profitScore = min($monthlyProfit / 10000, 3) * 10;
        
        // 투자 회수 기간 점수 (최대 20점)
        $recoupmentScore = max(0, 5 - $recoupmentPeriod) * 4;
        
        return min(round($marginScore + $roiScore + $profitScore + $recoupmentScore), 100);
    }

    /**
     * 추천 결과 저장
     *
     * @param array $recommendations 추천 결과
     * @return void
     */
    protected function saveRecommendations(array $recommendations): void
    {
        try {
            // 기존 추천 삭제
            ProductRecommendation::truncate();
            
            // 새 추천 저장
            foreach ($recommendations as $index => $recommendation) {
                ProductRecommendation::create([
                    'coupang_product_id' => $recommendation['coupang_product']['productId'] ?? null,
                    'domeggook_product_id' => $recommendation['best_match']['productId'] ?? null,
                    'margin_rate' => $recommendation['profitability']['margin_rate'] ?? 0,
                    'roi' => $recommendation['profitability']['roi'] ?? 0,
                    'monthly_profit' => $recommendation['profitability']['monthly_profit'] ?? 0,
                    'recoupment_period' => $recommendation['profitability']['recoupment_period'] ?? 0,
                    'score' => $recommendation['score'] ?? 0,
                    'rank' => $index + 1,
                    'data' => json_encode($recommendation),
                    'created_at' => Carbon::now()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('추천 결과 저장 오류: ' . $e->getMessage());
        }
    }
} 