<?php
/**
 * 플러그인의 메인 클래스
 *
 * @package RocketSourcer
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 플러그인의 모든 기능을 관리하는 메인 클래스
 */
class Rocket_Sourcer {

    /**
     * 플러그인 인스턴스
     *
     * @var Rocket_Sourcer
     */
    private static $instance = null;

    /**
     * 플러그인 생성자
     */
    public function __construct() {
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * 필요한 의존성 로드
     */
    private function load_dependencies() {
        // 관리자 기능 클래스 로드
        require_once ROCKET_SOURCER_PATH . 'admin/class-rocket-sourcer-admin.php';
        
        // 사용자 기능 클래스 로드
        require_once ROCKET_SOURCER_PATH . 'public/class-rocket-sourcer-public.php';
    }

    /**
     * 관리자 영역 훅 정의
     */
    private function define_admin_hooks() {
        $admin = new Rocket_Sourcer_Admin();
        
        // 관리자 메뉴 추가
        add_action('admin_menu', array($admin, 'add_plugin_menu'));
        
        // 관리자 스타일 및 스크립트 로드
        add_action('admin_enqueue_scripts', array($admin, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($admin, 'enqueue_scripts'));
    }

    /**
     * 사용자 영역 훅 정의
     */
    private function define_public_hooks() {
        $public = new Rocket_Sourcer_Public();
        
        // 프론트엔드 스타일 및 스크립트 로드
        add_action('wp_enqueue_scripts', array($public, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($public, 'enqueue_scripts'));
        
        // 단축코드 등록
        add_shortcode('rocket_sourcer', array($public, 'display_sourcer'));
    }

    /**
     * 플러그인 실행
     */
    public function run() {
        // 실행 로직
    }

    /**
     * 플러그인 활성화 시 실행
     */
    public static function activate() {
        // 데이터베이스 테이블 생성 등 활성화 시 필요한 작업
        self::create_database_tables();
    }

    /**
     * 플러그인 비활성화 시 실행
     */
    public static function deactivate() {
        // 비활성화 시 필요한 작업
    }

    /**
     * 데이터베이스 테이블 생성
     */
    private static function create_database_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // 키워드 테이블
        $table_keywords = $wpdb->prefix . 'rocket_sourcer_keywords';
        $sql_keywords = "CREATE TABLE $table_keywords (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            keyword varchar(100) NOT NULL,
            search_volume int NOT NULL,
            competition float NOT NULL,
            category varchar(50) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        // 제품 테이블
        $table_products = $wpdb->prefix . 'rocket_sourcer_products';
        $sql_products = "CREATE TABLE $table_products (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            keyword_id mediumint(9) NOT NULL,
            title varchar(255) NOT NULL,
            price float NOT NULL,
            rating float NOT NULL,
            reviews int NOT NULL,
            seller varchar(100) NOT NULL,
            url varchar(255) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY keyword_id (keyword_id)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_keywords);
        dbDelta($sql_products);
    }
} 