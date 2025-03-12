<?php
/**
 * 관리자용 AJAX 핸들러 클래스
 *
 * @package RocketSourcer
 */

class Rocket_Sourcer_Admin_Ajax {
    /**
     * 인스턴스
     *
     * @var Rocket_Sourcer_Admin_Ajax
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
     * @return Rocket_Sourcer_Admin_Ajax
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
        add_action('wp_ajax_analyze_keyword', array($this, 'handle_keyword_analysis'));
        add_action('wp_ajax_analyze_product', array($this, 'handle_product_analysis'));
        add_action('wp_ajax_search_overseas', array($this, 'handle_overseas_search'));
        add_action('wp_ajax_save_settings', array($this, 'handle_save_settings'));
        add_action('wp_ajax_get_dashboard_stats', array($this, 'handle_dashboard_stats'));
    }

    /**
     * 키워드 분석 처리
     */
    public function handle_keyword_analysis() {
        check_ajax_referer('rocket_sourcer_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('권한이 없습니다.');
        }

        $keyword = sanitize_text_field($_POST['keyword']);
        $category = sanitize_text_field($_POST['category']);

        try {
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

            // 분석 결과 저장
            $this->save_keyword_analysis($keyword, $analysis_result);

            wp_send_json_success($analysis_result);

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * 제품 분석 처리
     */
    public function handle_product_analysis() {
        check_ajax_referer('rocket_sourcer_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('권한이 없습니다.');
        }

        $product_url = esc_url_raw($_POST['product_url']);

        try {
            // 제품 정보 수집
            $product_data = $this->crawler->get_product_details($product_url);
            
            if (!$product_data) {
                throw new Exception('제품 정보 수집에 실패했습니다.');
            }

            // 해외 소싱 검색
            $overseas_results = $this->sourcing->search_by_image(
                $product_data['image_url'],
                'both'
            );

            // 제품 분석 수행
            $analysis_result = $this->analyzer->analyze_product(array_merge(
                $product_data,
                array('overseas_results' => $overseas_results)
            ));

            if (!$analysis_result) {
                throw new Exception('제품 분석에 실패했습니다.');
            }

            // 분석 결과 저장
            $this->save_product_analysis($product_url, $analysis_result);

            wp_send_json_success($analysis_result);

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * 해외 소싱 검색 처리
     */
    public function handle_overseas_search() {
        check_ajax_referer('rocket_sourcer_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('권한이 없습니다.');
        }

        $keyword = sanitize_text_field($_POST['keyword']);
        $platform = sanitize_text_field($_POST['platform']);

        try {
            if ($platform === '1688') {
                $results = $this->sourcing->search_1688($keyword);
            } else {
                $results = $this->sourcing->search_aliexpress($keyword);
            }

            if (!$results) {
                throw new Exception('해외 소싱 검색에 실패했습니다.');
            }

            wp_send_json_success($results);

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * 설정 저장 처리
     */
    public function handle_save_settings() {
        check_ajax_referer('rocket_sourcer_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('권한이 없습니다.');
        }

        $settings = array(
            'api_keys' => array(
                '1688' => sanitize_text_field($_POST['1688_api_key']),
                '1688_secret' => sanitize_text_field($_POST['1688_api_secret']),
                'aliexpress' => sanitize_text_field($_POST['aliexpress_api_key']),
                'aliexpress_secret' => sanitize_text_field($_POST['aliexpress_api_secret'])
            ),
            'analysis_options' => array(
                'min_search_volume' => intval($_POST['min_search_volume']),
                'max_competition' => intval($_POST['max_competition']),
                'min_margin' => floatval($_POST['min_margin'])
            ),
            'notification_options' => array(
                'email_notifications' => isset($_POST['email_notifications']),
                'notification_email' => sanitize_email($_POST['notification_email'])
            )
        );

        update_option('rocket_sourcer_settings', $settings);
        wp_send_json_success('설정이 저장되었습니다.');
    }

    /**
     * 대시보드 통계 처리
     */
    public function handle_dashboard_stats() {
        check_ajax_referer('rocket_sourcer_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('권한이 없습니다.');
        }

        try {
            $stats = array(
                'analyzed_keywords' => $this->get_analyzed_keywords_count(),
                'analyzed_products' => $this->get_analyzed_products_count(),
                'average_margin' => $this->get_average_margin(),
                'success_rate' => $this->get_success_rate()
            );

            wp_send_json_success($stats);

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * 키워드 분석 결과 저장
     *
     * @param string $keyword 키워드
     * @param array $result 분석 결과
     */
    private function save_keyword_analysis($keyword, $result) {
        $analyses = get_option('rocket_sourcer_keyword_analyses', array());
        $analyses[$keyword] = array_merge($result, array(
            'timestamp' => current_time('timestamp')
        ));
        update_option('rocket_sourcer_keyword_analyses', $analyses);
    }

    /**
     * 제품 분석 결과 저장
     *
     * @param string $product_url 제품 URL
     * @param array $result 분석 결과
     */
    private function save_product_analysis($product_url, $result) {
        $analyses = get_option('rocket_sourcer_product_analyses', array());
        $analyses[$product_url] = array_merge($result, array(
            'timestamp' => current_time('timestamp')
        ));
        update_option('rocket_sourcer_product_analyses', $analyses);
    }

    /**
     * 분석된 키워드 수 조회
     *
     * @return int 키워드 수
     */
    private function get_analyzed_keywords_count() {
        $analyses = get_option('rocket_sourcer_keyword_analyses', array());
        return count($analyses);
    }

    /**
     * 분석된 제품 수 조회
     *
     * @return int 제품 수
     */
    private function get_analyzed_products_count() {
        $analyses = get_option('rocket_sourcer_product_analyses', array());
        return count($analyses);
    }

    /**
     * 평균 마진율 계산
     *
     * @return float 평균 마진율
     */
    private function get_average_margin() {
        $analyses = get_option('rocket_sourcer_product_analyses', array());
        if (empty($analyses)) {
            return 0;
        }

        $total_margin = 0;
        foreach ($analyses as $analysis) {
            $total_margin += $analysis['profitability']['margin_rate'];
        }

        return round($total_margin / count($analyses), 2);
    }

    /**
     * 성공률 계산
     *
     * @return float 성공률
     */
    private function get_success_rate() {
        $analyses = get_option('rocket_sourcer_product_analyses', array());
        if (empty($analyses)) {
            return 0;
        }

        $success_count = 0;
        foreach ($analyses as $analysis) {
            if ($analysis['success_potential']['total_score'] >= 70) {
                $success_count++;
            }
        }

        return round(($success_count / count($analyses)) * 100, 2);
    }
} 