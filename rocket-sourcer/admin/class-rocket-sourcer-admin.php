<?php
/**
 * 플러그인의 관리자 기능을 정의하는 클래스
 *
 * @since      1.0.0
 * @package    Rocket_Sourcer
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 플러그인의 관리자 기능을 정의하는 클래스
 *
 * 이 클래스는 관리자 영역에서 플러그인의 모든 기능을 정의합니다.
 * 관리자 메뉴, 설정 페이지, 스타일 및 스크립트 등록 등을 처리합니다.
 *
 * @since      1.0.0
 * @package    Rocket_Sourcer
 * @author     AI Developer
 */
class Rocket_Sourcer_Admin {

    /**
     * 플러그인의 식별자
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    플러그인의 식별자
     */
    private $plugin_name;

    /**
     * 플러그인의 현재 버전
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    플러그인의 현재 버전
     */
    private $version;

    /**
     * 데이터베이스 클래스의 인스턴스
     *
     * @since    1.0.0
     * @access   private
     * @var      Rocket_Sourcer_DB    $db    데이터베이스 클래스의 인스턴스
     */
    private $db;

    /**
     * 클래스 생성자
     *
     * 플러그인의 식별자와 버전을 설정합니다.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       플러그인의 식별자
     * @param    string    $version           플러그인의 현재 버전
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->db = new Rocket_Sourcer_DB();
    }

    /**
     * 관리자 영역에서 사용할 스타일을 등록합니다.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, ROCKET_SOURCER_PLUGIN_URL . 'assets/css/rocket-sourcer-admin.css', array(), $this->version, 'all');
    }

    /**
     * 관리자 영역에서 사용할 스크립트를 등록합니다.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, ROCKET_SOURCER_PLUGIN_URL . 'assets/js/rocket-sourcer-admin.js', array('jquery'), $this->version, false);
        
        // AJAX URL 및 nonce 전달
        wp_localize_script($this->plugin_name, 'rocket_sourcer_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rocket_sourcer_admin_nonce')
        ));
    }

    /**
     * 관리자 메뉴를 추가합니다.
     *
     * @since    1.0.0
     */
    public function add_plugin_admin_menu() {
        // 메인 메뉴 추가
        add_menu_page(
            '로켓 소서 - 쿠팡 소싱 도구',
            '로켓 소서',
            'manage_options',
            $this->plugin_name,
            array($this, 'display_plugin_admin_dashboard'),
            'dashicons-chart-area',
            26
        );
        
        // 대시보드 서브메뉴
        add_submenu_page(
            $this->plugin_name,
            '대시보드',
            '대시보드',
            'manage_options',
            $this->plugin_name,
            array($this, 'display_plugin_admin_dashboard')
        );
        
        // 제품 관리 서브메뉴
        add_submenu_page(
            $this->plugin_name,
            '제품 관리',
            '제품 관리',
            'manage_options',
            $this->plugin_name . '-products',
            array($this, 'display_plugin_admin_products')
        );
        
        // 데이터 가져오기 서브메뉴
        add_submenu_page(
            $this->plugin_name,
            '데이터 가져오기',
            '데이터 가져오기',
            'manage_options',
            $this->plugin_name . '-import',
            array($this, 'display_plugin_admin_import')
        );
        
        // 설정 서브메뉴
        add_submenu_page(
            $this->plugin_name,
            '설정',
            '설정',
            'manage_options',
            $this->plugin_name . '-settings',
            array($this, 'display_plugin_admin_settings')
        );
    }

    /**
     * 플러그인 설정 페이지 링크를 추가합니다.
     *
     * @since    1.0.0
     * @param    array    $links    플러그인 액션 링크 배열
     * @return   array              수정된 플러그인 액션 링크 배열
     */
    public function add_action_links($links) {
        $settings_link = array(
            '<a href="' . admin_url('admin.php?page=' . $this->plugin_name . '-settings') . '">' . __('설정', 'rocket-sourcer') . '</a>',
        );
        return array_merge($settings_link, $links);
    }

    /**
     * 관리자 대시보드 페이지를 표시합니다.
     *
     * @since    1.0.0
     */
    public function display_plugin_admin_dashboard() {
        include_once ROCKET_SOURCER_PLUGIN_DIR . 'admin/partials/rocket-sourcer-admin-dashboard.php';
    }

    /**
     * 제품 관리 페이지를 표시합니다.
     *
     * @since    1.0.0
     */
    public function display_plugin_admin_products() {
        include_once ROCKET_SOURCER_PLUGIN_DIR . 'admin/partials/rocket-sourcer-admin-products.php';
    }

    /**
     * 데이터 가져오기 페이지를 표시합니다.
     *
     * @since    1.0.0
     */
    public function display_plugin_admin_import() {
        include_once ROCKET_SOURCER_PLUGIN_DIR . 'admin/partials/rocket-sourcer-admin-import.php';
    }

    /**
     * 설정 페이지를 표시합니다.
     *
     * @since    1.0.0
     */
    public function display_plugin_admin_settings() {
        include_once ROCKET_SOURCER_PLUGIN_DIR . 'admin/partials/rocket-sourcer-admin-settings.php';
    }

    /**
     * 설정 페이지를 초기화합니다.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        // API 키 설정
        register_setting(
            'rocket_sourcer_settings',
            'rocket_sourcer_api_key',
            array('sanitize_callback' => 'sanitize_text_field')
        );
        
        // 결과 페이지당 항목 수 설정
        register_setting(
            'rocket_sourcer_settings',
            'rocket_sourcer_results_per_page',
            array('sanitize_callback' => 'intval')
        );
        
        // 최소 이익률 설정
        register_setting(
            'rocket_sourcer_settings',
            'rocket_sourcer_min_profit_margin',
            array('sanitize_callback' => 'intval')
        );
        
        // 최소 ROI 설정
        register_setting(
            'rocket_sourcer_settings',
            'rocket_sourcer_min_roi',
            array('sanitize_callback' => 'intval')
        );
        
        // 기본 통화 설정
        register_setting(
            'rocket_sourcer_settings',
            'rocket_sourcer_default_currency',
            array('sanitize_callback' => 'sanitize_text_field')
        );
        
        // 데이터 소스 설정
        register_setting(
            'rocket_sourcer_settings',
            'rocket_sourcer_data_sources',
            array('sanitize_callback' => array($this, 'sanitize_data_sources'))
        );
        
        // 데이터 새로고침 간격 설정
        register_setting(
            'rocket_sourcer_settings',
            'rocket_sourcer_refresh_interval',
            array('sanitize_callback' => 'intval')
        );
        
        // 알림 활성화 설정
        register_setting(
            'rocket_sourcer_settings',
            'rocket_sourcer_enable_notifications',
            array('sanitize_callback' => 'intval')
        );
        
        // 분석 활성화 설정
        register_setting(
            'rocket_sourcer_settings',
            'rocket_sourcer_enable_analytics',
            array('sanitize_callback' => 'intval')
        );
    }

    /**
     * 데이터 소스 배열을 정리합니다.
     *
     * @since    1.0.0
     * @param    array    $sources    데이터 소스 배열
     * @return   array                정리된 데이터 소스 배열
     */
    public function sanitize_data_sources($sources) {
        if (!is_array($sources)) {
            return array();
        }
        
        $allowed_sources = array('coupang', 'amazon', 'aliexpress');
        $sanitized = array();
        
        foreach ($sources as $source) {
            if (in_array($source, $allowed_sources)) {
                $sanitized[] = $source;
            }
        }
        
        return $sanitized;
    }

    /**
     * 제품 데이터를 가져오는 AJAX 핸들러
     *
     * @since    1.0.0
     */
    public function ajax_import_products() {
        // 보안 검사
        check_ajax_referer('rocket_sourcer_admin_nonce', 'nonce');
        
        // 권한 검사
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => '권한이 없습니다.'));
        }
        
        // 소스 및 카테고리 가져오기
        $source = isset($_POST['source']) ? sanitize_text_field($_POST['source']) : '';
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        
        // 소스 유효성 검사
        $allowed_sources = array('coupang', 'amazon', 'aliexpress');
        if (!in_array($source, $allowed_sources)) {
            wp_send_json_error(array('message' => '유효하지 않은 소스입니다.'));
        }
        
        // 여기에 실제 데이터 가져오기 로직 구현
        // 예시: 외부 API 호출 또는 크롤링 로직
        
        // 성공 응답
        wp_send_json_success(array(
            'message' => '데이터를 성공적으로 가져왔습니다.',
            'source' => $source,
            'category' => $category,
            'count' => 10 // 예시 데이터 수
        ));
    }

    /**
     * 제품 삭제 AJAX 핸들러
     *
     * @since    1.0.0
     */
    public function ajax_delete_product() {
        // 보안 검사
        check_ajax_referer('rocket_sourcer_admin_nonce', 'nonce');
        
        // 권한 검사
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => '권한이 없습니다.'));
        }
        
        // 제품 ID 가져오기
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        
        if ($product_id <= 0) {
            wp_send_json_error(array('message' => '유효하지 않은 제품 ID입니다.'));
        }
        
        // 제품 삭제
        $result = $this->db->delete_product($product_id);
        
        if ($result) {
            wp_send_json_success(array('message' => '제품이 성공적으로 삭제되었습니다.'));
        } else {
            wp_send_json_error(array('message' => '제품 삭제 중 오류가 발생했습니다.'));
        }
    }
} 