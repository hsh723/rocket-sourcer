<?php
/**
 * 마진 계산 클래스
 *
 * @package    Rocket_Sourcer
 * @subpackage Rocket_Sourcer/includes
 */

class Rocket_Sourcer_Calculator {

    /**
     * 쿠팡 수수료율 (카테고리별)
     */
    private $category_fees = array(
        'fashion' => 0.11,    // 패션의류 11%
        'beauty' => 0.11,     // 뷰티 11%
        'food' => 0.10,       // 식품 10%
        'living' => 0.11,     // 생활용품 11%
        'digital' => 0.11,    // 디지털/가전 11%
        'sports' => 0.11,     // 스포츠/레저 11%
        'baby' => 0.11,       // 유아동 11%
        'pets' => 0.11        // 반려동물 11%
    );

    /**
     * 배송비 기본값
     */
    private $shipping_fees = array(
        'basic' => 3000,      // 기본 배송비
        'premium' => 5000,    // 프리미엄 배송비
        'rocket' => 4000      // 로켓배송 배송비
    );

    /**
     * 마진율 계산하기
     *
     * @param array $data 계산에 필요한 데이터
     * @return array 마진 계산 결과
     */
    public function calculate_margin($data) {
        // 필수 데이터 확인
        $required_fields = array('selling_price', 'cost_price', 'category');
        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                throw new Exception("필수 필드가 누락되었습니다: {$field}");
            }
        }

        // 기본값 설정
        $data = array_merge(array(
            'shipping_type' => 'basic',
            'is_free_shipping' => false,
            'additional_costs' => 0
        ), $data);

        // 판매 수수료 계산
        $commission_rate = isset($this->category_fees[$data['category']]) 
            ? $this->category_fees[$data['category']] 
            : 0.11;
        $commission = $data['selling_price'] * $commission_rate;

        // 배송비 계산
        $shipping_cost = $data['is_free_shipping'] ? 0 : $this->shipping_fees[$data['shipping_type']];

        // 순수익 계산
        $revenue = $data['selling_price'];
        $total_costs = $data['cost_price'] + $commission + $shipping_cost + $data['additional_costs'];
        $net_profit = $revenue - $total_costs;

        // 마진율 계산
        $margin_rate = ($net_profit / $revenue) * 100;

        return array(
            'summary' => array(
                'revenue' => $revenue,
                'total_costs' => $total_costs,
                'net_profit' => $net_profit,
                'margin_rate' => round($margin_rate, 2)
            ),
            'details' => array(
                'selling_price' => $data['selling_price'],
                'cost_price' => $data['cost_price'],
                'commission' => array(
                    'rate' => $commission_rate * 100,
                    'amount' => $commission
                ),
                'shipping' => array(
                    'type' => $data['shipping_type'],
                    'cost' => $shipping_cost,
                    'is_free' => $data['is_free_shipping']
                ),
                'additional_costs' => $data['additional_costs']
            )
        );
    }

    /**
     * 손익분기점 계산하기
     *
     * @param array $data 계산에 필요한 데이터
     * @return array 손익분기점 계산 결과
     */
    public function calculate_break_even($data) {
        // 필수 데이터 확인
        $required_fields = array('fixed_costs', 'cost_price', 'selling_price', 'category');
        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                throw new Exception("필수 필드가 누락되었습니다: {$field}");
            }
        }

        // 기본값 설정
        $data = array_merge(array(
            'shipping_type' => 'basic',
            'is_free_shipping' => false,
            'additional_costs' => 0
        ), $data);

        // 단위당 변동 비용 계산
        $commission_rate = isset($this->category_fees[$data['category']]) 
            ? $this->category_fees[$data['category']] 
            : 0.11;
        $commission_per_unit = $data['selling_price'] * $commission_rate;
        $shipping_cost = $data['is_free_shipping'] ? 0 : $this->shipping_fees[$data['shipping_type']];
        $variable_cost_per_unit = $data['cost_price'] + $commission_per_unit + $shipping_cost + $data['additional_costs'];

        // 단위당 공헌이익 계산
        $contribution_margin_per_unit = $data['selling_price'] - $variable_cost_per_unit;

        // 손익분기점 수량 계산
        $break_even_quantity = ceil($data['fixed_costs'] / $contribution_margin_per_unit);

        // 손익분기점 금액 계산
        $break_even_amount = $break_even_quantity * $data['selling_price'];

        return array(
            'break_even_point' => array(
                'quantity' => $break_even_quantity,
                'amount' => $break_even_amount
            ),
            'unit_economics' => array(
                'selling_price' => $data['selling_price'],
                'variable_costs' => array(
                    'cost_price' => $data['cost_price'],
                    'commission' => $commission_per_unit,
                    'shipping' => $shipping_cost,
                    'additional' => $data['additional_costs'],
                    'total' => $variable_cost_per_unit
                ),
                'contribution_margin' => $contribution_margin_per_unit,
                'contribution_margin_ratio' => ($contribution_margin_per_unit / $data['selling_price']) * 100
            ),
            'fixed_costs' => $data['fixed_costs']
        );
    }

    /**
     * 최적 판매가 추천하기
     *
     * @param array $data 계산에 필요한 데이터
     * @return array 최적 판매가 추천 결과
     */
    public function recommend_price($data) {
        // 필수 데이터 확인
        $required_fields = array('cost_price', 'target_margin', 'category');
        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                throw new Exception("필수 필드가 누락되었습니다: {$field}");
            }
        }

        // 기본값 설정
        $data = array_merge(array(
            'shipping_type' => 'basic',
            'is_free_shipping' => false,
            'additional_costs' => 0,
            'min_margin' => 20,
            'max_margin' => 50
        ), $data);

        // 수수료율 가져오기
        $commission_rate = isset($this->category_fees[$data['category']]) 
            ? $this->category_fees[$data['category']] 
            : 0.11;

        // 배송비 계산
        $shipping_cost = $data['is_free_shipping'] ? 0 : $this->shipping_fees[$data['shipping_type']];

        // 목표 마진율에 따른 판매가 계산
        $base_costs = $data['cost_price'] + $shipping_cost + $data['additional_costs'];
        $selling_price = $base_costs / (1 - $commission_rate - ($data['target_margin'] / 100));

        // 최소/최대 마진율 적용
        $min_price = $base_costs / (1 - $commission_rate - ($data['min_margin'] / 100));
        $max_price = $base_costs / (1 - $commission_rate - ($data['max_margin'] / 100));

        // 가격 반올림 (100원 단위)
        $selling_price = ceil($selling_price / 100) * 100;
        $min_price = ceil($min_price / 100) * 100;
        $max_price = ceil($max_price / 100) * 100;

        return array(
            'recommended_price' => $selling_price,
            'price_range' => array(
                'min' => $min_price,
                'max' => $max_price
            ),
            'margins' => array(
                'target' => $data['target_margin'],
                'min' => $data['min_margin'],
                'max' => $data['max_margin']
            ),
            'costs' => array(
                'base' => $base_costs,
                'commission_rate' => $commission_rate * 100
            )
        );
    }
} 