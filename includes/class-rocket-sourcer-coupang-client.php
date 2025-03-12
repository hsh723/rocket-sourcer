<?php
/**
 * 쿠팡 API 클라이언트 클래스
 *
 * @package    Rocket_Sourcer
 * @subpackage Rocket_Sourcer/includes
 */

class Rocket_Sourcer_Coupang_Client {

    /**
     * API 엔드포인트
     */
    private $api_endpoint = 'https://api-gateway.coupang.com';

    /**
     * API 키
     */
    private $access_key;

    /**
     * API 시크릿 키
     */
    private $secret_key;

    /**
     * 생성자
     */
    public function __construct() {
        $this->access_key = get_option('rocket_sourcer_api_key', '');
        $this->secret_key = get_option('rocket_sourcer_api_secret', '');
    }

    /**
     * API 요청 헤더 생성
     *
     * @param string $method HTTP 메서드
     * @param string $path API 경로
     * @param array $parameters 요청 파라미터
     * @return array 헤더 배열
     */
    private function generate_headers($method, $path, $parameters = array()) {
        $timestamp = round(microtime(true) * 1000);
        $query_string = http_build_query($parameters);

        // 서명 문자열 생성
        $message = $method . ' ' . $path . '?' . $query_string . '&timestamp=' . $timestamp;
        $signature = hash_hmac('sha256', $message, $this->secret_key);

        return array(
            'Authorization' => 'CEA algorithm=HmacSHA256, access-key=' . $this->access_key . ', signed-date=' . $timestamp . ', signature=' . $signature,
            'Content-Type' => 'application/json',
            'X-Requested-By' => 'Rocket-Sourcer'
        );
    }

    /**
     * API 요청 실행
     *
     * @param string $method HTTP 메서드
     * @param string $path API 경로
     * @param array $parameters 요청 파라미터
     * @return array|WP_Error 응답 데이터 또는 오류
     */
    private function request($method, $path, $parameters = array()) {
        // API 키 확인
        if (empty($this->access_key) || empty($this->secret_key)) {
            return new WP_Error('api_key_missing', 'API 키가 설정되지 않았습니다.');
        }

        $url = $this->api_endpoint . $path;
        if ($method === 'GET' && !empty($parameters)) {
            $url .= '?' . http_build_query($parameters);
        }

        $headers = $this->generate_headers($method, $path, $parameters);

        $args = array(
            'method' => $method,
            'headers' => $headers,
            'timeout' => 30
        );

        if ($method === 'POST') {
            $args['body'] = wp_json_encode($parameters);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_decode_error', 'JSON 디코딩 오류: ' . json_last_error_msg());
        }

        return $data;
    }

    /**
     * 인기 키워드 가져오기
     *
     * @param string $category 카테고리
     * @param int $limit 결과 수
     * @return array|WP_Error 키워드 목록 또는 오류
     */
    public function get_popular_keywords($category, $limit = 10) {
        // 실제 API 연동 전 샘플 데이터 반환
        return array(
            'keywords' => array(
                array(
                    'keyword' => '여성 가방',
                    'volume' => 15000,
                    'competition' => 'HIGH',
                    'trend' => 'UP',
                    'trend_percentage' => 15
                ),
                array(
                    'keyword' => '운동화',
                    'volume' => 12000,
                    'competition' => 'MEDIUM',
                    'trend' => 'UP',
                    'trend_percentage' => 8
                ),
                // ... 더 많은 샘플 데이터
            )
        );
    }

    /**
     * 키워드 분석하기
     *
     * @param string $keyword 키워드
     * @return array|WP_Error 분석 결과 또는 오류
     */
    public function analyze_keyword($keyword) {
        // 실제 API 연동 전 샘플 데이터 반환
        return array(
            'keyword' => $keyword,
            'total_volume' => 25000,
            'average_volume' => 20000,
            'competition_level' => 'MEDIUM',
            'trend_direction' => 'UP',
            'trend_percentage' => 12,
            'estimated_cpc' => 850,
            'monthly_volumes' => array(
                array('month' => '2024-01', 'volume' => 22000),
                array('month' => '2024-02', 'volume' => 24000),
                array('month' => '2024-03', 'volume' => 25000)
            ),
            'related_keywords' => array(
                array('keyword' => $keyword . ' 추천', 'volume' => 5000),
                array('keyword' => $keyword . ' 리뷰', 'volume' => 3000),
                array('keyword' => '인기 ' . $keyword, 'volume' => 2000)
            )
        );
    }

    /**
     * 제품 검색하기
     *
     * @param string $keyword 검색어
     * @param array $options 검색 옵션
     * @return array|WP_Error 검색 결과 또는 오류
     */
    public function search_products($keyword, $options = array()) {
        // 실제 API 연동 전 샘플 데이터 반환
        return array(
            'products' => array(
                array(
                    'id' => 'P' . rand(1000000, 9999999),
                    'title' => '샘플 제품 1 - ' . $keyword,
                    'price' => rand(10000, 100000),
                    'original_price' => rand(15000, 120000),
                    'category' => '패션의류',
                    'rating' => rand(35, 50) / 10,
                    'review_count' => rand(100, 1000),
                    'seller' => '샘플 판매자 1',
                    'image_url' => 'https://via.placeholder.com/150'
                ),
                array(
                    'id' => 'P' . rand(1000000, 9999999),
                    'title' => '샘플 제품 2 - ' . $keyword,
                    'price' => rand(10000, 100000),
                    'original_price' => rand(15000, 120000),
                    'category' => '패션의류',
                    'rating' => rand(35, 50) / 10,
                    'review_count' => rand(100, 1000),
                    'seller' => '샘플 판매자 2',
                    'image_url' => 'https://via.placeholder.com/150'
                )
            )
        );
    }

    /**
     * 제품 분석하기
     *
     * @param string $product_id 제품 ID
     * @return array|WP_Error 분석 결과 또는 오류
     */
    public function analyze_product($product_id) {
        // 실제 API 연동 전 샘플 데이터 반환
        return array(
            'product_id' => $product_id,
            'daily_sales' => rand(10, 100),
            'monthly_sales' => rand(300, 3000),
            'market_share' => rand(1, 100) / 10,
            'competition_level' => array('LOW', 'MEDIUM', 'HIGH')[rand(0, 2)],
            'price_history' => array(
                array('date' => '2024-01-01', 'price' => rand(10000, 100000)),
                array('date' => '2024-02-01', 'price' => rand(10000, 100000)),
                array('date' => '2024-03-01', 'price' => rand(10000, 100000))
            ),
            'review_analysis' => array(
                'positive' => rand(60, 80),
                'neutral' => rand(10, 20),
                'negative' => rand(5, 15)
            )
        );
    }

    /**
     * API 상태 확인
     *
     * @return bool API 사용 가능 여부
     */
    public function check_api_status() {
        return !empty($this->access_key) && !empty($this->secret_key);
    }
} 