<?php
/**
 * 플러그인 활성화 클래스
 *
 * @package    Rocket_Sourcer
 * @subpackage Rocket_Sourcer/includes
 */

class Rocket_Sourcer_Activator {

    /**
     * 플러그인 활성화 시 실행되는 메서드
     */
    public static function activate() {
        self::create_database_tables();
        self::set_default_options();
    }

    /**
     * 데이터베이스 테이블 생성
     */
    private static function create_database_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // 키워드 분석 결과 테이블
        $table_keywords = $wpdb->prefix . 'rocket_sourcer_keywords';
        $sql_keywords = "CREATE TABLE IF NOT EXISTS $table_keywords (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            keyword varchar(255) NOT NULL,
            category varchar(50) NOT NULL,
            volume int(11) NOT NULL DEFAULT 0,
            competition varchar(20) NOT NULL,
            trend varchar(20) NOT NULL,
            trend_percentage int(11) NOT NULL,
            estimated_cpc int(11) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY keyword (keyword),
            KEY category (category)
        ) $charset_collate;";

        // 제품 분석 결과 테이블
        $table_products = $wpdb->prefix . 'rocket_sourcer_products';
        $sql_products = "CREATE TABLE IF NOT EXISTS $table_products (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_id varchar(50) NOT NULL,
            title varchar(255) NOT NULL,
            price int(11) NOT NULL,
            original_price int(11) NOT NULL,
            category varchar(50) NOT NULL,
            rating decimal(3,1) NOT NULL,
            review_count int(11) NOT NULL,
            seller varchar(100) NOT NULL,
            daily_sales int(11) NOT NULL,
            monthly_sales int(11) NOT NULL,
            market_share decimal(5,2) NOT NULL,
            competition_level varchar(20) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY product_id (product_id),
            KEY category (category)
        ) $charset_collate;";

        // 마진 계산 결과 테이블
        $table_margins = $wpdb->prefix . 'rocket_sourcer_margins';
        $sql_margins = "CREATE TABLE IF NOT EXISTS $table_margins (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_id varchar(50) NOT NULL,
            selling_price int(11) NOT NULL,
            cost_price int(11) NOT NULL,
            shipping_cost int(11) NOT NULL,
            commission int(11) NOT NULL,
            additional_costs int(11) NOT NULL,
            margin_rate decimal(5,2) NOT NULL,
            break_even_quantity int(11) NOT NULL,
            break_even_amount int(11) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY product_id (product_id)
        ) $charset_collate;";

        // 검색 기록 테이블
        $table_searches = $wpdb->prefix . 'rocket_sourcer_searches';
        $sql_searches = "CREATE TABLE IF NOT EXISTS $table_searches (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            search_type varchar(20) NOT NULL,
            keyword varchar(255) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_keywords);
        dbDelta($sql_products);
        dbDelta($sql_margins);
        dbDelta($sql_searches);
    }

    /**
     * 기본 옵션 설정
     */
    private static function set_default_options() {
        $default_options = array(
            'api_key' => '',
            'auto_analysis' => '0',
            'save_results' => '1',
            'result_lifetime' => 30,
            'notification_email' => get_option('admin_email'),
            'currency' => 'KRW',
            'language' => 'ko'
        );

        foreach ($default_options as $key => $value) {
            if (get_option('rocket_sourcer_' . $key) === false) {
                add_option('rocket_sourcer_' . $key, $value);
            }
        }
    }
} 