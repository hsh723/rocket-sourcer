<?php

namespace App\Services\Recommendation;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class ProfitabilityAnalyzer
{
    // 기본 비용 설정
    protected array $defaultCosts = [
        'shipping_rate' => 0.05,      // 판매가 대비 배송비 비율
        'platform_fee_rate' => 0.10,  // 판매가 대비 플랫폼 수수료 비율
        'tax_rate' => 0.10,           // 판매가 대비 세금 비율
        'packaging_cost' => 1000,     // 포장비 (원)
        'handling_cost' => 500,       // 취급비 (원)
        'return_rate' => 0.02,        // 반품률
        'marketing_rate' => 0.05      // 판매가 대비 마케팅 비용 비율
    ];
    
    /**
     * 제품 수익성 분석
     */
    public function analyzeProfitability(array $product): array
    {
        try {
            // 필수 데이터 확인
            $wholesalePrice = $product['wholesale_price'] ?? 0;
            $retailPrice = $product['retail_price'] ?? 0;
            
            if ($wholesalePrice <= 0 || $retailPrice <= 0) {
                return $this->getDefaultProfitabilityResult();
            }
            
            // 비용 계산
            $costs = $this->calculateCosts($wholesalePrice, $retailPrice, $product);
            
            // 수익 계산
            $revenue = $retailPrice;
            $totalCost = $wholesalePrice + $costs['total_additional_costs'];
            $profit = $revenue - $totalCost;
            $marginRate = ($profit / $revenue) * 100;
            $roi = ($profit / $totalCost) * 100;
            
            // 손익분기점 계산
            $breakEvenVolume = $this->calculateBreakEvenVolume($product, $costs);
            
            // 수익성 등급 계산
            $profitabilityRating = $this->calculateProfitabilityRating($marginRate, $roi);
            
            // 결과 반환
            return [
                'wholesale_price' => $wholesalePrice,
                'retail_price' => $retailPrice,
                'costs' => $costs,
                'profit' => $profit,
                'margin_rate' => $marginRate,
                'roi' => $roi,
                'break_even_volume' => $breakEvenVolume,
                'profitability_rating' => $profitabilityRating,
                'is_profitable' => $profit > 0
            ];
        } catch (\Exception $e) {
            Log::error('수익성 분석 중 오류 발생: ' . $e->getMessage(), [
                'product' => $product,
                'exception' => $e
            ]);
            
            return $this->getDefaultProfitabilityResult();
        }
    }
    
    /**
     * 가격 차이 기반 수익성 분석
     */
    public function analyzePriceGapProfitability(float $wholesalePrice, float $retailPrice): array
    {
        try {
            if ($wholesalePrice <= 0 || $retailPrice <= 0) {
                return $this->getDefaultProfitabilityResult();
            }
            
            // 제품 데이터 구성
            $product = [
                'wholesale_price' => $wholesalePrice,
                'retail_price' => $retailPrice
            ];
            
            // 기본 수익성 분석 로직 활용
            return $this->analyzeProfitability($product);
        } catch (\Exception $e) {
            Log::error('가격 차이 수익성 분석 중 오류 발생: ' . $e->getMessage(), [
                'wholesale_price' => $wholesalePrice,
                'retail_price' => $retailPrice,
                'exception' => $e
            ]);
            
            return $this->getDefaultProfitabilityResult();
        }
    }
    
    /**
     * 최적 판매 가격 계산
     */
    public function calculateOptimalPrice(array $product, float $targetMargin = 30.0): float
    {
        try {
            $wholesalePrice = $product['wholesale_price'] ?? 0;
            
            if ($wholesalePrice <= 0) {
                return 0;
            }
            
            // 목표 마진율을 달성하기 위한 최소 판매가 계산
            $targetMarginRate = $targetMargin / 100;
            $additionalCostRate = 
                $this->defaultCosts['shipping_rate'] + 
                $this->defaultCosts['platform_fee_rate'] + 
                $this->defaultCosts['tax_rate'] + 
                $this->defaultCosts['marketing_rate'];
            
            $fixedCosts = 
                $this->defaultCosts['packaging_cost'] + 
                $this->defaultCosts['handling_cost'];
            
            // 판매가 = (도매가 + 고정비용) / (1 - 마진율 - 추가비용율)
            $optimalPrice = ($wholesalePrice + $fixedCosts) / (1 - $targetMarginRate - $additionalCostRate);
            
            // 가격 반올림 (1000원 단위)
            return ceil($optimalPrice / 1000) * 1000;
        } catch (\Exception $e) {
            Log::error('최적 가격 계산 중 오류 발생: ' . $e->getMessage(), [
                'product' => $product,
                'target_margin' => $targetMargin,
                'exception' => $e
            ]);
            
            return 0;
        }
    }
    
    /**
     * 가격 시뮬레이션 수행
     */
    public function simulatePricing(array $product, array $pricePoints = []): array
    {
        try {
            $wholesalePrice = $product['wholesale_price'] ?? 0;
            
            if ($wholesalePrice <= 0) {
                return [];
            }
            
            // 가격 포인트가 제공되지 않은 경우 기본 가격 포인트 생성
            if (empty($pricePoints)) {
                $basePrice = $this->calculateOptimalPrice($product, 20);
                $pricePoints = [
                    $basePrice * 0.8,
                    $basePrice * 0.9,
                    $basePrice,
                    $basePrice * 1.1,
                    $basePrice * 1.2
                ];
            }
            
            // 각 가격 포인트에 대한 수익성 분석
            $simulations = [];
            foreach ($pricePoints as $price) {
                $productWithPrice = $product;
                $productWithPrice['retail_price'] = $price;
                
                $profitability = $this->analyzeProfitability($productWithPrice);
                $simulations[] = [
                    'price' => $price,
                    'profit' => $profitability['profit'],
                    'margin_rate' => $profitability['margin_rate'],
                    'roi' => $profitability['roi'],
                    'profitability_rating' => $profitability['profitability_rating']
                ];
            }
            
            return $simulations;
        } catch (\Exception $e) {
            Log::error('가격 시뮬레이션 중 오류 발생: ' . $e->getMessage(), [
                'product' => $product,
                'price_points' => $pricePoints,
                'exception' => $e
            ]);
            
            return [];
        }
    }
    
    /**
     * 비용 계산
     */
    protected function calculateCosts(float $wholesalePrice, float $retailPrice, array $product): array
    {
        // 제품별 비용 설정 (기본값 사용 또는 제품별 설정 적용)
        $costs = $this->defaultCosts;
        
        // 제품별 설정이 있는 경우 적용
        if (isset($product['costs'])) {
            $costs = array_merge($costs, $product['costs']);
        }
        
        // 비용 계산
        $shippingCost = $retailPrice * $costs['shipping_rate'];
        $platformFee = $retailPrice * $costs['platform_fee_rate'];
        $taxCost = $retailPrice * $costs['tax_rate'];
        $packagingCost = $costs['packaging_cost'];
        $handlingCost = $costs['handling_cost'];
        $returnCost = $retailPrice * $costs['return_rate'];
        $marketingCost = $retailPrice * $costs['marketing_rate'];
        
        // 총 추가 비용
        $totalAdditionalCosts = 
            $shippingCost + 
            $platformFee + 
            $taxCost + 
            $packagingCost + 
            $handlingCost + 
            $returnCost + 
            $marketingCost;
        
        return [
            'shipping_cost' => $shippingCost,
            'platform_fee' => $platformFee,
            'tax_cost' => $taxCost,
            'packaging_cost' => $packagingCost,
            'handling_cost' => $handlingCost,
            'return_cost' => $returnCost,
            'marketing_cost' => $marketingCost,
            'total_additional_costs' => $totalAdditionalCosts
        ];
    }
    
    /**
     * 손익분기점 계산
     */
    protected function calculateBreakEvenVolume(array $product, array $costs): int
    {
        // 고정 비용 (월간)
        $fixedCosts = $product['fixed_costs'] ?? 100000; // 기본값: 10만원
        
        // 단위당 이익
        $unitProfit = $product['retail_price'] - $product['wholesale_price'] - $costs['total_additional_costs'];
        
        if ($unitProfit <= 0) {
            return PHP_INT_MAX; // 단위당 이익이 0 이하면 손익분기점 도달 불가
        }
        
        // 손익분기점 = 고정 비용 / 단위당 이익
        return ceil($fixedCosts / $unitProfit);
    }
    
    /**
     * 수익성 등급 계산
     */
    protected function calculateProfitabilityRating(float $marginRate, float $roi): string
    {
        // 마진율과 ROI를 기반으로 수익성 등급 계산
        $score = ($marginRate * 0.7) + ($roi * 0.3);
        
        if ($score >= 40) {
            return 'A'; // 매우 높은 수익성
        } else if ($score >= 30) {
            return 'B'; // 높은 수익성
        } else if ($score >= 20) {
            return 'C'; // 보통 수익성
        } else if ($score >= 10) {
            return 'D'; // 낮은 수익성
        } else {
            return 'F'; // 매우 낮은 수익성
        }
    }
    
    /**
     * 기본 수익성 결과 반환
     */
    protected function getDefaultProfitabilityResult(): array
    {
        return [
            'wholesale_price' => 0,
            'retail_price' => 0,
            'costs' => [
                'shipping_cost' => 0,
                'platform_fee' => 0,
                'tax_cost' => 0,
                'packaging_cost' => 0,
                'handling_cost' => 0,
                'return_cost' => 0,
                'marketing_cost' => 0,
                'total_additional_costs' => 0
            ],
            'profit' => 0,
            'margin_rate' => 0,
            'roi' => 0,
            'break_even_volume' => 0,
            'profitability_rating' => 'F',
            'is_profitable' => false
        ];
    }
} 