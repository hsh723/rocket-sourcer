<?php
/**
 * 관리자 영역 기능을 처리하는 클래스
 *
 * @package    Rocket_Sourcer
 * @subpackage Rocket_Sourcer/admin
 */
class Rocket_Sourcer_Admin {

    /**
     * 플러그인의 고유 식별자
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    플러그인의 고유 식별자
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
     * 초기화
     *
     * @since    1.0.0
     * @param    string    $plugin_name    플러그인의 고유 식별자
     * @param    string    $version        플러그인의 현재 버전
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * 관리자 스타일 등록
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'css/rocket-sourcer-admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * 관리자 스크립트 등록
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'js/rocket-sourcer-admin.js',
            array('jquery'),
            $this->version,
            false
        );
    }

    /**
     * 관리자 메뉴 추가
     *
     * @since    1.0.0
     */
    public function add_plugin_admin_menu() {
        add_menu_page(
            '로켓소서 - 쿠팡 소싱 도구',
            '로켓소서',
            'manage_options',
            $this->plugin_name,
            array($this, 'display_plugin_admin_page'),
            'dashicons-chart-area',
            26
        );

        add_submenu_page(
            $this->plugin_name,
            '키워드 분석',
            '키워드 분석',
            'manage_options',
            $this->plugin_name . '-keywords',
            array($this, 'display_plugin_keywords_page')
        );

        add_submenu_page(
            $this->plugin_name,
            '제품 분석',
            '제품 분석',
            'manage_options',
            $this->plugin_name . '-products',
            array($this, 'display_plugin_products_page')
        );

        add_submenu_page(
            $this->plugin_name,
            '설정',
            '설정',
            'manage_options',
            $this->plugin_name . '-settings',
            array($this, 'display_plugin_settings_page')
        );
    }

    /**
     * 메인 관리자 페이지 출력
     *
     * @since    1.0.0
     */
    public function display_plugin_admin_page() {
        require_once plugin_dir_path(__FILE__) . 'partials/rocket-sourcer-admin-main.php';
    }

    /**
     * 키워드 분석 페이지 출력
     *
     * @since    1.0.0
     */
    public function display_plugin_keywords_page() {
        require_once plugin_dir_path(__FILE__) . 'partials/rocket-sourcer-admin-keywords.php';
    }

    /**
     * 제품 분석 페이지 출력
     *
     * @since    1.0.0
     */
    public function display_plugin_products_page() {
        require_once plugin_dir_path(__FILE__) . 'partials/rocket-sourcer-admin-products.php';
    }

    /**
     * 설정 페이지 출력
     *
     * @since    1.0.0
     */
    public function display_plugin_settings_page() {
        require_once plugin_dir_path(__FILE__) . 'partials/rocket-sourcer-admin-settings.php';
    }
} 