<?php
/**
 * 해외 소싱 연동 클래스
 *
 * @package RocketSourcer
 */

class Rocket_Sourcer_Sourcing {
    /**
     * 인스턴스
     *
     * @var Rocket_Sourcer_Sourcing
     */
    private static $instance = null;

    /**
     * API 설정
     *
     * @var array
     */
    private $api_settings;

    /**
     * 싱글톤 인스턴스 반환
     *
     * @return Rocket_Sourcer_Sourcing
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 생성자
     */
    private function __construct() {
        $this->api_settings = get_option('rocket_sourcer_api_settings', array());
    }

    /**
     * 1688 제품 검색
     *
     * @param string $keyword 검색 키워드
     * @param array $options 검색 옵션
     * @return array 검색 결과
     */
    public function search_1688($keyword, $options = array()) {
        try {
            if (empty($this->api_settings['1688_api_key'])) {
                throw new Exception('1688 API 키가 설정되지 않았습니다.');
            }

            $api_key = $this->api_settings['1688_api_key'];
            $api_secret = $this->api_settings['1688_api_secret'];

            $params = array(
                'keywords' => $keyword,
                'page' => isset($options['page']) ? $options['page'] : 1,
                'pageSize' => isset($options['pageSize']) ? $options['pageSize'] : 20
            );

            $response = $this->make_1688_request('alibaba.product.search', $params);
            return $this->parse_1688_results($response);

        } catch (Exception $e) {
            $this->log_error('1688 검색 오류: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 알리익스프레스 제품 검색
     *
     * @param string $keyword 검색 키워드
     * @param array $options 검색 옵션
     * @return array 검색 결과
     */
    public function search_aliexpress($keyword, $options = array()) {
        try {
            if (empty($this->api_settings['aliexpress_api_key'])) {
                throw new Exception('알리익스프레스 API 키가 설정되지 않았습니다.');
            }

            $api_key = $this->api_settings['aliexpress_api_key'];
            $api_secret = $this->api_settings['aliexpress_api_secret'];

            $params = array(
                'keywords' => $keyword,
                'page' => isset($options['page']) ? $options['page'] : 1,
                'pageSize' => isset($options['pageSize']) ? $options['pageSize'] : 20
            );

            $response = $this->make_aliexpress_request('api.search', $params);
            return $this->parse_aliexpress_results($response);

        } catch (Exception $e) {
            $this->log_error('알리익스프레스 검색 오류: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 이미지 기반 유사 제품 검색
     *
     * @param string $image_url 이미지 URL
     * @param string $platform 검색 플랫폼 (1688 또는 aliexpress)
     * @return array 검색 결과
     */
    public function search_by_image($image_url, $platform = '1688') {
        try {
            $api_key = $platform === '1688' ? 
                $this->api_settings['1688_api_key'] : 
                $this->api_settings['aliexpress_api_key'];

            if (empty($api_key)) {
                throw new Exception($platform . ' API 키가 설정되지 않았습니다.');
            }

            $params = array(
                'image_url' => $image_url
            );

            $response = $platform === '1688' ?
                $this->make_1688_request('alibaba.product.image.search', $params) :
                $this->make_aliexpress_request('api.image.search', $params);

            return $platform === '1688' ?
                $this->parse_1688_results($response) :
                $this->parse_aliexpress_results($response);

        } catch (Exception $e) {
            $this->log_error('이미지 검색 오류: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 배송비 계산
     *
     * @param float $weight 중량 (kg)
     * @param array $dimensions 치수 (cm)
     * @param string $shipping_method 배송 방법
     * @return array 배송비 계산 결과
     */
    public function calculate_shipping_cost($weight, $dimensions, $shipping_method = 'express') {
        try {
            // 부피 중량 계산
            $volume_weight = ($dimensions['length'] * $dimensions['width'] * $dimensions['height']) / 6000;
            $chargeable_weight = max($weight, $volume_weight);

            // 배송 방법별 요금 계산
            $rates = $this->get_shipping_rates($shipping_method);
            $base_rate = $rates['base_rate'];
            $per_kg_rate = $rates['per_kg_rate'];

            $shipping_cost = $base_rate + ($chargeable_weight * $per_kg_rate);

            return array(
                'weight' => $weight,
                'volume_weight' => $volume_weight,
                'chargeable_weight' => $chargeable_weight,
                'shipping_method' => $shipping_method,
                'base_rate' => $base_rate,
                'per_kg_rate' => $per_kg_rate,
                'total_cost' => $shipping_cost
            );

        } catch (Exception $e) {
            $this->log_error('배송비 계산 오류: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 관세 및 부가세 계산
     *
     * @param float $product_cost 제품 원가
     * @param float $shipping_cost 배송비
     * @param string $category 제품 카테고리
     * @return array 관세 계산 결과
     */
    public function calculate_duties($product_cost, $shipping_cost, $category) {
        try {
            // 카테고리별 관세율 조회
            $duty_rate = $this->get_duty_rate($category);
            
            // CIF 가격 계산 (원가 + 운임 + 보험료)
            $insurance = ($product_cost + $shipping_cost) * 0.002; // 보험료는 CIF의 0.2% 가정
            $cif_price = $product_cost + $shipping_cost + $insurance;

            // 관세 계산
            $duty = $cif_price * ($duty_rate / 100);

            // 부가세 계산 (CIF + 관세의 10%)
            $vat = ($cif_price + $duty) * 0.1;

            return array(
                'cif_price' => $cif_price,
                'duty_rate' => $duty_rate,
                'duty_amount' => $duty,
                'vat_amount' => $vat,
                'total_tax' => $duty + $vat,
                'total_cost' => $cif_price + $duty + $vat
            );

        } catch (Exception $e) {
            $this->log_error('관세 계산 오류: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 1688 API 요청
     *
     * @param string $method API 메서드
     * @param array $params 요청 파라미터
     * @return array 응답 데이터
     */
    private function make_1688_request($method, $params) {
        // API 요청 구현
        return array();
    }

    /**
     * 알리익스프레스 API 요청
     *
     * @param string $method API 메서드
     * @param array $params 요청 파라미터
     * @return array 응답 데이터
     */
    private function make_aliexpress_request($method, $params) {
        // API 요청 구현
        return array();
    }

    /**
     * 배송 요금 조회
     *
     * @param string $shipping_method 배송 방법
     * @return array 배송 요금 정보
     */
    private function get_shipping_rates($shipping_method) {
        $rates = array(
            'express' => array(
                'base_rate' => 15000,
                'per_kg_rate' => 8000
            ),
            'standard' => array(
                'base_rate' => 10000,
                'per_kg_rate' => 5000
            ),
            'economy' => array(
                'base_rate' => 8000,
                'per_kg_rate' => 3000
            )
        );

        return isset($rates[$shipping_method]) ? $rates[$shipping_method] : $rates['standard'];
    }

    /**
     * 카테고리별 관세율 조회
     *
     * @param string $category 제품 카테고리
     * @return float 관세율
     */
    private function get_duty_rate($category) {
        $duty_rates = array(
            'clothing' => 13,
            'electronics' => 8,
            'accessories' => 8,
            'cosmetics' => 6.5,
            'toys' => 8,
            'default' => 8
        );

        return isset($duty_rates[$category]) ? $duty_rates[$category] : $duty_rates['default'];
    }

    /**
     * 오류 로깅
     *
     * @param string $message 오류 메시지
     */
    private function log_error($message) {
        error_log('[Rocket Sourcer Sourcing] ' . $message);
    }
} 