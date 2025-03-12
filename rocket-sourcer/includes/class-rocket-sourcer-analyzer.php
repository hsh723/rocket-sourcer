<?php
/**
 * 데이터 처리 및 분석 클래스
 *
 * @package RocketSourcer
 */

class Rocket_Sourcer_Analyzer {
    /**
     * 인스턴스
     *
     * @var Rocket_Sourcer_Analyzer
     */
    private static $instance = null;

    /**
     * 캐시 만료 시간 (초)
     *
     * @var int
     */
    private $cache_expiration = 3600;

    /**
     * 싱글톤 인스턴스 반환
     *
     * @return Rocket_Sourcer_Analyzer
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 키워드 평가
     *
     * @param string $keyword 키워드
     * @param array $search_data 검색 데이터
     * @return array 평가 결과
     */
    public function evaluate_keyword($keyword, $search_data) {
        try {
            $cache_key = 'rocket_sourcer_keyword_' . md5($keyword);
            $cached_result = get_transient($cache_key);

            if (false !== $cached_result) {
                return $cached_result;
            }

            // 검색량 점수 계산 (0-100)
            $volume_score = $this->calculate_volume_score($search_data['monthly_volume']);

            // 경쟁강도 점수 계산 (0-100)
            $competition_score = $this->calculate_competition_score($search_data['competition_data']);

            // 트렌드 분석
            $trend_data = $this->analyze_trend($search_data['monthly_trends']);

            // 종합 점수 계산 (0-100)
            $total_score = $this->calculate_total_score($volume_score, $competition_score, $trend_data['trend_score']);

            $result = array(
                'keyword' => $keyword,
                'volume_score' => $volume_score,
                'competition_score' => $competition_score,
                'trend_analysis' => $trend_data,
                'total_score' => $total_score,
                'recommendation' => $this->get_keyword_recommendation($total_score),
                'timestamp' => current_time('timestamp')
            );

            set_transient($cache_key, $result, $this->cache_expiration);
            return $result;

        } catch (Exception $e) {
            $this->log_error('키워드 평가 오류: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 제품 분석
     *
     * @param array $product_data 제품 데이터
     * @return array 분석 결과
     */
    public function analyze_product($product_data) {
        try {
            // 기본 정보 분석
            $basic_analysis = array(
                'price_range' => $this->analyze_price_range($product_data['price']),
                'rating_analysis' => $this->analyze_rating($product_data['rating'], $product_data['review_count']),
                'shipping_analysis' => $this->analyze_shipping($product_data['shipping_info'])
            );

            // 수익성 분석
            $profitability = $this->analyze_profitability(
                $product_data['price'],
                $product_data['cost'],
                $product_data['shipping_cost']
            );

            // 판매 예측
            $sales_forecast = $this->forecast_sales(
                $product_data['historical_sales'],
                $product_data['category_data']
            );

            // 성공 가능성 평가
            $success_potential = $this->evaluate_success_potential(
                $basic_analysis,
                $profitability,
                $sales_forecast
            );

            return array(
                'basic_analysis' => $basic_analysis,
                'profitability' => $profitability,
                'sales_forecast' => $sales_forecast,
                'success_potential' => $success_potential,
                'recommendations' => $this->get_product_recommendations($success_potential)
            );

        } catch (Exception $e) {
            $this->log_error('제품 분석 오류: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 마진 계산
     *
     * @param array $data 계산에 필요한 데이터
     * @return array 계산 결과
     */
    public function calculate_margin($data) {
        try {
            // 기본 비용 계산
            $base_cost = $data['product_cost'] + $data['shipping_cost'];
            
            // 쿠팡 수수료 계산
            $coupang_fee = $data['selling_price'] * ($data['coupang_fee_rate'] / 100);
            
            // 부가세 계산
            $vat = $data['selling_price'] * 0.1;
            
            // 반품 예상 비용 계산
            $return_cost = $data['selling_price'] * ($data['expected_return_rate'] / 100);
            
            // 순수익 계산
            $revenue = $data['selling_price'];
            $total_cost = $base_cost + $coupang_fee + $vat + $return_cost;
            $net_profit = $revenue - $total_cost;
            
            // 이익률 계산
            $profit_margin = ($net_profit / $revenue) * 100;
            
            // 손익분기점 계산
            $break_even = $this->calculate_break_even_point(
                $base_cost,
                $data['selling_price'],
                $profit_margin
            );
            
            // 시나리오 분석
            $scenarios = $this->analyze_profit_scenarios(
                $data['selling_price'],
                $base_cost,
                $profit_margin
            );

            return array(
                'revenue' => $revenue,
                'total_cost' => $total_cost,
                'net_profit' => $net_profit,
                'profit_margin' => $profit_margin,
                'break_even_point' => $break_even,
                'scenarios' => $scenarios,
                'recommendations' => $this->get_margin_recommendations($profit_margin)
            );

        } catch (Exception $e) {
            $this->log_error('마진 계산 오류: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 검색량 점수 계산
     *
     * @param int $monthly_volume 월간 검색량
     * @return float 검색량 점수
     */
    private function calculate_volume_score($monthly_volume) {
        // 로그 스케일 적용 (1-100)
        $score = min(100, max(1, log10($monthly_volume) * 20));
        return round($score, 2);
    }

    /**
     * 경쟁강도 점수 계산
     *
     * @param array $competition_data 경쟁 데이터
     * @return float 경쟁강도 점수
     */
    private function calculate_competition_score($competition_data) {
        $factors = array(
            'seller_count' => 0.3,
            'price_competition' => 0.3,
            'review_competition' => 0.2,
            'brand_presence' => 0.2
        );

        $score = 0;
        foreach ($factors as $factor => $weight) {
            $score += $competition_data[$factor] * $weight;
        }

        return round($score, 2);
    }

    /**
     * 트렌드 분석
     *
     * @param array $monthly_trends 월간 트렌드 데이터
     * @return array 트렌드 분석 결과
     */
    private function analyze_trend($monthly_trends) {
        // 선형 회귀 분석
        $n = count($monthly_trends);
        $sum_x = 0;
        $sum_y = 0;
        $sum_xy = 0;
        $sum_xx = 0;

        foreach ($monthly_trends as $i => $volume) {
            $sum_x += $i;
            $sum_y += $volume;
            $sum_xy += $i * $volume;
            $sum_xx += $i * $i;
        }

        $slope = ($n * $sum_xy - $sum_x * $sum_y) / ($n * $sum_xx - $sum_x * $sum_x);
        $trend_score = min(100, max(0, 50 + ($slope * 10)));

        return array(
            'trend_score' => round($trend_score, 2),
            'slope' => $slope,
            'is_growing' => $slope > 0,
            'growth_rate' => $slope > 0 ? ($slope / ($sum_y / $n)) * 100 : 0
        );
    }

    /**
     * 종합 점수 계산
     *
     * @param float $volume_score 검색량 점수
     * @param float $competition_score 경쟁강도 점수
     * @param float $trend_score 트렌드 점수
     * @return float 종합 점수
     */
    private function calculate_total_score($volume_score, $competition_score, $trend_score) {
        $weights = array(
            'volume' => 0.4,
            'competition' => 0.4,
            'trend' => 0.2
        );

        $total = ($volume_score * $weights['volume']) +
                ((100 - $competition_score) * $weights['competition']) +
                ($trend_score * $weights['trend']);

        return round($total, 2);
    }

    /**
     * 키워드 추천 메시지 생성
     *
     * @param float $total_score 종합 점수
     * @return string 추천 메시지
     */
    private function get_keyword_recommendation($total_score) {
        if ($total_score >= 80) {
            return '매우 유망한 키워드입니다. 즉시 진입을 추천합니다.';
        } elseif ($total_score >= 60) {
            return '잠재력이 있는 키워드입니다. 추가 분석 후 진입을 고려하세요.';
        } elseif ($total_score >= 40) {
            return '중간 수준의 키워드입니다. 신중한 접근이 필요합니다.';
        } else {
            return '경쟁이 치열하거나 수요가 낮은 키워드입니다. 다른 키워드를 고려하세요.';
        }
    }

    /**
     * 제품 추천 메시지 생성
     *
     * @param array $success_potential 성공 가능성 데이터
     * @return array 추천 메시지 목록
     */
    private function get_product_recommendations($success_potential) {
        $recommendations = array();

        if ($success_potential['market_fit'] < 60) {
            $recommendations[] = '시장 적합도가 낮습니다. 제품 차별화 전략이 필요합니다.';
        }

        if ($success_potential['profitability'] < 50) {
            $recommendations[] = '수익성이 낮습니다. 원가 절감 또는 판매가 조정을 고려하세요.';
        }

        if ($success_potential['competition_level'] > 70) {
            $recommendations[] = '경쟁이 치열합니다. 틈새시장을 공략하거나 차별화 포인트를 강화하세요.';
        }

        return $recommendations;
    }

    /**
     * 마진 추천 메시지 생성
     *
     * @param float $profit_margin 이익률
     * @return array 추천 메시지 목록
     */
    private function get_margin_recommendations($profit_margin) {
        $recommendations = array();

        if ($profit_margin < 15) {
            $recommendations[] = '이익률이 낮습니다. 원가 절감 또는 판매가 인상을 고려하세요.';
        } elseif ($profit_margin < 25) {
            $recommendations[] = '이익률이 적정 수준입니다. 운영 효율화로 수익성을 높이세요.';
        } else {
            $recommendations[] = '이익률이 양호합니다. 현재 가격 정책을 유지하면서 판매량 증대에 집중하세요.';
        }

        return $recommendations;
    }

    /**
     * 오류 로깅
     *
     * @param string $message 오류 메시지
     */
    private function log_error($message) {
        error_log('[Rocket Sourcer Analyzer] ' . $message);
    }
} 