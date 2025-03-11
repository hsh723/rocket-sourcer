<?php
/**
 * 플러그인 활성화 시 실행되는 기능을 정의하는 클래스
 *
 * @since      1.0.0
 * @package    Rocket_Sourcer
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 플러그인 활성화 시 실행되는 기능을 정의하는 클래스
 *
 * 이 클래스는 플러그인이 활성화될 때 필요한 데이터베이스 테이블을 생성하고 초기 설정을 수행합니다.
 *
 * @since      1.0.0
 * @package    Rocket_Sourcer
 * @author     AI Developer
 */
class Rocket_Sourcer_Activator {

    /**
     * 플러그인 활성화 시 실행되는 메서드
     *
     * 이 메서드는 플러그인이 활성화될 때 필요한 데이터베이스 테이블을 생성하고 초기 설정을 수행합니다.
     *
     * @since    1.0.0
     */
    public static function activate() {
        // 데이터베이스 테이블 생성
        self::create_tables();
        
        // 초기 설정 값 저장
        self::set_default_options();
        
        // 플러그인 버전 저장
        update_option('rocket_sourcer_version', ROCKET_SOURCER_VERSION);
        
        // 플러시 리라이트 규칙
        flush_rewrite_rules();
    }
    
    /**
     * 필요한 데이터베이스 테이블을 생성합니다.
     *
     * @since    1.0.0
     * @access   private
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // 소싱 데이터 테이블
        $table_name = $wpdb->prefix . 'rocket_sourcer_products';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_name varchar(255) NOT NULL,
            product_url varchar(255) NOT NULL,
            product_image varchar(255) DEFAULT '' NOT NULL,
            product_price decimal(10,2) NOT NULL,
            product_category varchar(100) DEFAULT '' NOT NULL,
            product_tags varchar(255) DEFAULT '' NOT NULL,
            product_rating decimal(3,2) DEFAULT '0.00' NOT NULL,
            product_reviews int(11) DEFAULT '0' NOT NULL,
            product_sales int(11) DEFAULT '0' NOT NULL,
            product_profit decimal(10,2) DEFAULT '0.00' NOT NULL,
            product_roi decimal(5,2) DEFAULT '0.00' NOT NULL,
            product_source varchar(100) DEFAULT '' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY product_category (product_category),
            KEY product_rating (product_rating),
            KEY product_sales (product_sales),
            KEY product_roi (product_roi)
        ) $charset_collate;";
        
        // 사용자 검색 기록 테이블
        $table_name_searches = $wpdb->prefix . 'rocket_sourcer_searches';
        
        $sql .= "CREATE TABLE $table_name_searches (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            search_term varchar(255) NOT NULL,
            search_filters text DEFAULT NULL,
            search_results int(11) DEFAULT '0' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY search_term (search_term)
        ) $charset_collate;";
        
        // 사용자 즐겨찾기 테이블
        $table_name_favorites = $wpdb->prefix . 'rocket_sourcer_favorites';
        
        $sql .= "CREATE TABLE $table_name_favorites (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            product_id bigint(20) NOT NULL,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY user_product (user_id, product_id),
            KEY user_id (user_id),
            KEY product_id (product_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * 플러그인의 기본 설정 값을 저장합니다.
     *
     * @since    1.0.0
     * @access   private
     */
    private static function set_default_options() {
        $options = array(
            'api_key' => '',
            'results_per_page' => 20,
            'min_profit_margin' => 30,
            'min_roi' => 50,
            'default_currency' => 'KRW',
            'data_sources' => array('coupang', 'amazon', 'aliexpress'),
            'refresh_interval' => 24, // 시간 단위
            'enable_notifications' => 1,
            'enable_analytics' => 1
        );
        
        foreach ($options as $option_name => $option_value) {
            update_option('rocket_sourcer_' . $option_name, $option_value);
        }
    }
} 