<?php
/**
 * 프론트엔드용 AJAX 핸들러 클래스
 *
 * @package RocketSourcer
 */

class Rocket_Sourcer_Public_Ajax {
    /**
     * 인스턴스
     *
     * @var Rocket_Sourcer_Public_Ajax
     */
    private static $instance = null;

    /**
     * 크롤러 인스턴스
     *
     * @var Rocket_Sourcer_Crawler
     */
    private $crawler;

    /**
     * 분석기 인스턴스
     *
     * @var Rocket_Sourcer_Analyzer
     */
    private $analyzer;

    /**
     * 소싱 인스턴스
     *
     * @var Rocket_Sourcer_Sourcing
     */
    private $sourcing;

    /**
     * 싱글톤 인스턴스 반환
     *
     * @return Rocket_Sourcer_Public_Ajax
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
        $this->crawler = Rocket_Sourcer_Crawler::get_instance();
        $this->analyzer = Rocket_Sourcer_Analyzer::get_instance();
        $this->sourcing = Rocket_Sourcer_Sourcing::get_instance();

        // AJAX 액션 등록
        add_action('wp_ajax_nopriv_public_analyze_keyword', array($this, 'handle_public_keyword_analysis'));
        add_action('wp_ajax_public_analyze_keyword', array($this, 'handle_public_keyword_analysis'));
        
        add_action('wp_ajax_nopriv_public_analyze_product', array($this, 'handle_public_product_analysis'));
        add_action('wp_ajax_public_analyze_product', array($this, 'handle_public_product_analysis'));
        
        add_action('wp_ajax_nopriv_public_calculate_margin', array($this, 'handle_public_margin_calculation'));
        add_action('wp_ajax_public_calculate_margin', array($this, 'handle_public_margin_calculation'));
    }

    /**
     * 공개 키워드 분석 처리
     */
    public function handle_public_keyword_analysis() {
        check_ajax_referer('rocket_sourcer_public', 'nonce');

        $keyword = sanitize_text_field($_POST['keyword']);
        $category = sanitize_text_field($_POST['category']);

        try {
            // 일일 요청 제한 확인
            if (!$this->check_daily_limit('keyword_analysis')) {
                throw new Exception('일일 분석 한도를 초과했습니다. 내일 다시 시도해주세요.');
            }

            // 쿠팡 검색 데이터 수집
            $search_data = $this->crawler->analyze_keyword($keyword, $category);
            
            if (!$search_data) {
                throw new Exception('검색 데이터 수집에 실패했습니다.');
            }

            // 키워드 분석 수행
            $analysis_result = $this->analyzer->evaluate_keyword($keyword, $search_data);
            
            if (!$analysis_result) {
                throw new Exception('키워드 분석에 실패했습니다.');
            }

            // 공개용 결과 필터링
            $public_result = $this->filter_public_keyword_result($analysis_result);

            // 일일 요청 카운트 증가
            $this->increment_daily_count('keyword_analysis');

            wp_send_json_success($public_result);

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * 공개 제품 분석 처리
     */
    public function handle_public_product_analysis() {
        check_ajax_referer('rocket_sourcer_public', 'nonce');

        $product_url = esc_url_raw($_POST['product_url']);

        try {
            // 일일 요청 제한 확인
            if (!$this->check_daily_limit('product_analysis')) {
                throw new Exception('일일 분석 한도를 초과했습니다. 내일 다시 시도해주세요.');
            }

            // 제품 정보 수집
            $product_data = $this->crawler->get_product_details($product_url);
            
            if (!$product_data) {
                throw new Exception('제품 정보 수집에 실패했습니다.');
            }

            // 제품 분석 수행
            $analysis_result = $this->analyzer->analyze_product($product_data);

            if (!$analysis_result) {
                throw new Exception('제품 분석에 실패했습니다.');
            }

            // 공개용 결과 필터링
            $public_result = $this->filter_public_product_result($analysis_result);

            // 일일 요청 카운트 증가
            $this->increment_daily_count('product_analysis');

            wp_send_json_success($public_result);

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * 공개 마진 계산 처리
     */
    public function handle_public_margin_calculation() {
        check_ajax_referer('rocket_sourcer_public', 'nonce');

        try {
            // 일일 요청 제한 확인
            if (!$this->check_daily_limit('margin_calculation')) {
                throw new Exception('일일 계산 한도를 초과했습니다. 내일 다시 시도해주세요.');
            }

            $data = array(
                'product_cost' => floatval($_POST['product_cost']),
                'selling_price' => floatval($_POST['selling_price']),
                'shipping_cost' => floatval($_POST['shipping_cost']),
                'coupang_fee_rate' => floatval($_POST['coupang_fee_rate']),
                'expected_return_rate' => floatval($_POST['expected_return_rate'])
            );

            // 입력값 검증
            $this->validate_margin_input($data);

            // 마진 계산 수행
            $calculation_result = $this->analyzer->calculate_margin($data);

            if (!$calculation_result) {
                throw new Exception('마진 계산에 실패했습니다.');
            }

            // 공개용 결과 필터링
            $public_result = $this->filter_public_margin_result($calculation_result);

            // 일일 요청 카운트 증가
            $this->increment_daily_count('margin_calculation');

            wp_send_json_success($public_result);

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * 일일 요청 제한 확인
     *
     * @param string $type 요청 유형
     * @return bool 제한 내 여부
     */
    private function check_daily_limit($type) {
        $limits = array(
            'keyword_analysis' => 10,
            'product_analysis' => 10,
            'margin_calculation' => 20
        );

        $today = date('Y-m-d');
        $counts = get_option('rocket_sourcer_daily_counts_' . $today, array());

        return !isset($counts[$type]) || $counts[$type] < $limits[$type];
    }

    /**
     * 일일 요청 카운트 증가
     *
     * @param string $type 요청 유형
     */
    private function increment_daily_count($type) {
        $today = date('Y-m-d');
        $counts = get_option('rocket_sourcer_daily_counts_' . $today, array());
        
        if (!isset($counts[$type])) {
            $counts[$type] = 0;
        }
        
        $counts[$type]++;
        update_option('rocket_sourcer_daily_counts_' . $today, $counts);
    }

    /**
     * 마진 계산 입력값 검증
     *
     * @param array $data 입력 데이터
     * @throws Exception 검증 실패시 예외 발생
     */
    private function validate_margin_input($data) {
        if ($data['product_cost'] <= 0) {
            throw new Exception('상품 원가는 0보다 커야 합니다.');
        }

        if ($data['selling_price'] <= 0) {
            throw new Exception('판매가는 0보다 커야 합니다.');
        }

        if ($data['shipping_cost'] < 0) {
            throw new Exception('배송비는 0 이상이어야 합니다.');
        }

        if ($data['coupang_fee_rate'] < 0 || $data['coupang_fee_rate'] > 100) {
            throw new Exception('쿠팡 수수료율은 0에서 100 사이여야 합니다.');
        }

        if ($data['expected_return_rate'] < 0 || $data['expected_return_rate'] > 100) {
            throw new Exception('예상 반품률은 0에서 100 사이여야 합니다.');
        }
    }

    /**
     * 공개용 키워드 분석 결과 필터링
     *
     * @param array $result 전체 분석 결과
     * @return array 필터링된 결과
     */
    private function filter_public_keyword_result($result) {
        return array(
            'keyword' => $result['keyword'],
            'volume_score' => $result['volume_score'],
            'competition_score' => $result['competition_score'],
            'trend_analysis' => array(
                'trend_score' => $result['trend_analysis']['trend_score'],
                'is_growing' => $result['trend_analysis']['is_growing']
            ),
            'total_score' => $result['total_score'],
            'recommendation' => $result['recommendation']
        );
    }

    /**
     * 공개용 제품 분석 결과 필터링
     *
     * @param array $result 전체 분석 결과
     * @return array 필터링된 결과
     */
    private function filter_public_product_result($result) {
        return array(
            'basic_analysis' => array(
                'price_range' => $result['basic_analysis']['price_range'],
                'rating_analysis' => $result['basic_analysis']['rating_analysis']
            ),
            'success_potential' => array(
                'market_fit' => $result['success_potential']['market_fit'],
                'competition_level' => $result['success_potential']['competition_level']
            ),
            'recommendations' => $result['recommendations']
        );
    }

    /**
     * 공개용 마진 계산 결과 필터링
     *
     * @param array $result 전체 계산 결과
     * @return array 필터링된 결과
     */
    private function filter_public_margin_result($result) {
        return array(
            'revenue' => $result['revenue'],
            'total_cost' => $result['total_cost'],
            'net_profit' => $result['net_profit'],
            'profit_margin' => $result['profit_margin'],
            'break_even_point' => $result['break_even_point'],
            'recommendations' => $result['recommendations']
        );
    }
} 