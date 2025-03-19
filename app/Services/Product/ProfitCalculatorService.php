<?php

namespace RocketSourcer\Services\Product;

use Psr\Log\LoggerInterface;
use RocketSourcer\Core\Cache;
use RocketSourcer\Models\Product;
use RocketSourcer\Services\Coupang\CoupangProductService;

class ProfitCalculatorService
{
    protected CoupangProductService $coupangProductService;
    protected Cache $cache;
    protected LoggerInterface $logger;

    public function __construct(
        CoupangProductService $coupangProductService,
        Cache $cache,
        LoggerInterface $logger
    ) {
        $this->coupangProductService = $coupangProductService;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    /**
     * 수익성 분석
     */
    public function calculateProfit(Product $product, array $options = []): array
    {
        $cacheKey = "profit_analysis:{$product->getId()}";
        
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }

        try {
            // 제품 가격 정보 조회
            $priceInfo = $this->coupangProductService->getProductPrice($product->getProductId());
            
            if (!$priceInfo->isSuccess()) {
                throw new \Exception($priceInfo->getMessage());
            }

            // 기본 비용 계산
            $costs = $this->calculateCosts($product, $priceInfo->getData());
            
            // 수익 계산
            $revenues = $this->calculateRevenues($product, $priceInfo->getData());
            
            // 마진 계산
            $margins = $this->calculateMargins($revenues, $costs);

            $result = [
                'costs' => $costs,
                'revenues' => $revenues,
                'margins' => $margins,
                'profitability_metrics' => $this->calculateProfitabilityMetrics($revenues, $costs),
                'breakeven_analysis' => $this->calculateBreakeven($costs, $margins),
                'sensitivity_analysis' => $this->performSensitivityAnalysis($product, $costs, $margins),
                'recommendations' => $this->generateRecommendations($margins, $costs),
            ];

            // 캐시에 결과 저장
            $this->cache->set($cacheKey, $result, 3600);

            return $result;

        } catch (\Exception $e) {
            $this->logger->error('수익성 분석 실패', [
                'product_id' => $product->getId(),
                'product_name' => $product->getName(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * 비용 계산
     */
    private function calculateCosts(Product $product, array $priceData): array
    {
        // 상품 원가
        $costPrice = $priceData['cost_price'] ?? 0;
        
        // 배송비
        $shippingCost = $priceData['shipping_cost'] ?? 0;
        
        // 플랫폼 수수료
        $platformFee = $this->calculatePlatformFee($product->getPrice());
        
        // 결제 수수료
        $paymentFee = $this->calculatePaymentFee($product->getPrice());
        
        // 마케팅 비용
        $marketingCost = $this->estimateMarketingCost($product);
        
        // 기타 운영 비용
        $operationalCost = $this->calculateOperationalCost($product);

        $totalCost = $costPrice + $shippingCost + $platformFee + $paymentFee + $marketingCost + $operationalCost;

        return [
            'cost_price' => round($costPrice, 2),
            'shipping_cost' => round($shippingCost, 2),
            'platform_fee' => round($platformFee, 2),
            'payment_fee' => round($paymentFee, 2),
            'marketing_cost' => round($marketingCost, 2),
            'operational_cost' => round($operationalCost, 2),
            'total_cost' => round($totalCost, 2),
            'cost_breakdown' => [
                'cost_price_ratio' => $this->calculateRatio($costPrice, $totalCost),
                'shipping_cost_ratio' => $this->calculateRatio($shippingCost, $totalCost),
                'platform_fee_ratio' => $this->calculateRatio($platformFee, $totalCost),
                'payment_fee_ratio' => $this->calculateRatio($paymentFee, $totalCost),
                'marketing_cost_ratio' => $this->calculateRatio($marketingCost, $totalCost),
                'operational_cost_ratio' => $this->calculateRatio($operationalCost, $totalCost),
            ],
        ];
    }

    /**
     * 수익 계산
     */
    private function calculateRevenues(Product $product, array $priceData): array
    {
        $price = $product->getPrice();
        $estimatedSales = $this->estimateMonthlySales($product);
        
        $grossRevenue = $price * $estimatedSales;
        $netRevenue = $grossRevenue - ($priceData['refund_rate'] ?? 0) * $grossRevenue;

        return [
            'unit_price' => round($price, 2),
            'estimated_monthly_sales' => $estimatedSales,
            'gross_revenue' => round($grossRevenue, 2),
            'refunds' => round(($priceData['refund_rate'] ?? 0) * $grossRevenue, 2),
            'net_revenue' => round($netRevenue, 2),
        ];
    }

    /**
     * 마진 계산
     */
    private function calculateMargins(array $revenues, array $costs): array
    {
        $grossMargin = $revenues['gross_revenue'] - $costs['cost_price'];
        $netMargin = $revenues['net_revenue'] - $costs['total_cost'];
        
        $grossMarginRatio = $this->calculateRatio($grossMargin, $revenues['gross_revenue']);
        $netMarginRatio = $this->calculateRatio($netMargin, $revenues['net_revenue']);

        return [
            'gross_margin' => round($grossMargin, 2),
            'net_margin' => round($netMargin, 2),
            'gross_margin_ratio' => round($grossMarginRatio, 2),
            'net_margin_ratio' => round($netMarginRatio, 2),
            'roi' => round($this->calculateROI($netMargin, $costs['total_cost']), 2),
        ];
    }

    /**
     * 수익성 지표 계산
     */
    private function calculateProfitabilityMetrics(array $revenues, array $costs): array
    {
        return [
            'gross_profit_margin' => $this->calculateRatio(
                $revenues['gross_revenue'] - $costs['cost_price'],
                $revenues['gross_revenue']
            ),
            'operating_profit_margin' => $this->calculateRatio(
                $revenues['net_revenue'] - $costs['total_cost'],
                $revenues['net_revenue']
            ),
            'cost_to_revenue_ratio' => $this->calculateRatio(
                $costs['total_cost'],
                $revenues['net_revenue']
            ),
            'breakeven_point' => $this->calculateBreakevenPoint($costs, $revenues['unit_price']),
        ];
    }

    /**
     * 손익분기점 분석
     */
    private function calculateBreakeven(array $costs, array $margins): array
    {
        $fixedCosts = $costs['operational_cost'] + $costs['marketing_cost'];
        $variableCosts = $costs['cost_price'] + $costs['shipping_cost'] + 
                        $costs['platform_fee'] + $costs['payment_fee'];
        
        $contributionMargin = $margins['gross_margin'] - $variableCosts;
        $breakevenUnits = $contributionMargin > 0 ? 
            ceil($fixedCosts / $contributionMargin) : 0;

        return [
            'fixed_costs' => round($fixedCosts, 2),
            'variable_costs_per_unit' => round($variableCosts, 2),
            'contribution_margin_per_unit' => round($contributionMargin, 2),
            'breakeven_units' => $breakevenUnits,
            'breakeven_revenue' => round($breakevenUnits * $contributionMargin, 2),
        ];
    }

    /**
     * 민감도 분석
     */
    private function performSensitivityAnalysis(
        Product $product,
        array $costs,
        array $margins
    ): array {
        $scenarios = [
            'optimistic' => [
                'price_change' => 1.1,
                'cost_change' => 0.95,
                'sales_change' => 1.2
            ],
            'pessimistic' => [
                'price_change' => 0.9,
                'cost_change' => 1.05,
                'sales_change' => 0.8
            ],
            'moderate' => [
                'price_change' => 1.05,
                'cost_change' => 1.02,
                'sales_change' => 1.1
            ]
        ];

        $results = [];
        foreach ($scenarios as $scenario => $changes) {
            $results[$scenario] = [
                'net_margin' => round($margins['net_margin'] * 
                    $changes['price_change'] * 
                    $changes['sales_change'] / 
                    $changes['cost_change'], 2),
                'roi' => round($this->calculateROI(
                    $margins['net_margin'] * 
                    $changes['price_change'] * 
                    $changes['sales_change'],
                    $costs['total_cost'] * $changes['cost_change']
                ), 2)
            ];
        }

        return $results;
    }

    /**
     * 추천사항 생성
     */
    private function generateRecommendations(array $margins, array $costs): array
    {
        $recommendations = [];

        // 마진율 기반 추천
        if ($margins['net_margin_ratio'] < 0.15) {
            $recommendations[] = [
                'type' => 'margin_improvement',
                'description' => '순마진이 낮습니다. 원가 절감 또는 판매가 조정을 고려하세요.',
                'priority' => 'high'
            ];
        }

        // 비용 구조 기반 추천
        if ($costs['cost_breakdown']['marketing_cost_ratio'] > 0.3) {
            $recommendations[] = [
                'type' => 'cost_optimization',
                'description' => '마케팅 비용이 높습니다. ROI를 개선하세요.',
                'priority' => 'medium'
            ];
        }

        // ROI 기반 추천
        if ($margins['roi'] < 0.2) {
            $recommendations[] = [
                'type' => 'profitability',
                'description' => 'ROI가 낮습니다. 수익성 개선이 필요합니다.',
                'priority' => 'high'
            ];
        }

        return $recommendations;
    }

    /**
     * 플랫폼 수수료 계산
     */
    private function calculatePlatformFee(float $price): float
    {
        // 쿠팡 기본 수수료율 (카테고리별로 다를 수 있음)
        return $price * 0.1;
    }

    /**
     * 결제 수수료 계산
     */
    private function calculatePaymentFee(float $price): float
    {
        // 일반적인 PG사 수수료율
        return $price * 0.032;
    }

    /**
     * 마케팅 비용 추정
     */
    private function estimateMarketingCost(Product $product): float
    {
        // 상품 가격의 10%를 마케팅 비용으로 가정
        return $product->getPrice() * 0.1;
    }

    /**
     * 운영 비용 계산
     */
    private function calculateOperationalCost(Product $product): float
    {
        // 상품 가격의 5%를 운영 비용으로 가정
        return $product->getPrice() * 0.05;
    }

    /**
     * 월간 판매량 추정
     */
    private function estimateMonthlySales(Product $product): int
    {
        // 리뷰 수를 기반으로 한 판매량 추정
        $reviewCount = $product->getReviewCount();
        $reviewToSalesRatio = 0.1; // 10%의 구매자가 리뷰를 작성한다고 가정
        
        return ceil($reviewCount / $reviewToSalesRatio);
    }

    /**
     * 비율 계산
     */
    private function calculateRatio(float $value, float $total): float
    {
        if ($total == 0) {
            return 0;
        }
        return ($value / $total) * 100;
    }

    /**
     * ROI 계산
     */
    private function calculateROI(float $profit, float $cost): float
    {
        if ($cost == 0) {
            return 0;
        }
        return ($profit / $cost) * 100;
    }

    /**
     * 손익분기점 계산
     */
    private function calculateBreakevenPoint(array $costs, float $unitPrice): float
    {
        $fixedCosts = $costs['operational_cost'] + $costs['marketing_cost'];
        $variableCosts = $costs['cost_price'] + $costs['shipping_cost'] + 
                        $costs['platform_fee'] + $costs['payment_fee'];
        
        $contributionMargin = $unitPrice - $variableCosts;
        
        if ($contributionMargin <= 0) {
            return 0;
        }
        
        return ceil($fixedCosts / $contributionMargin);
    }
} 