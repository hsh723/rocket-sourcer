<?php
/**
 * 플러그인 활성화 시 실행되는 기능을 정의하는 클래스
 *
 * @package    Rocket_Sourcer
 * @subpackage Rocket_Sourcer/includes
 */
class Rocket_Sourcer_Activator {

    /**
     * 플러그인 활성화 시 실행되는 메서드
     *
     * @since    1.0.0
     */
    public static function activate() {
        global $wpdb;

        // 데이터베이스 문자셋 설정
        $charset_collate = $wpdb->get_charset_collate();

        // 키워드 분석 결과 테이블
        $table_name = $wpdb->prefix . 'rocket_sourcer_keywords';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            keyword varchar(255) NOT NULL,
            category varchar(50) NOT NULL,
            volume int(11) NOT NULL DEFAULT 0,
            competition varchar(20) NOT NULL,
            trend varchar(20) NOT NULL,
            trend_percentage int(11) NOT NULL DEFAULT 0,
            estimated_cpc int(11) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY keyword (keyword),
            KEY category (category)
        ) $charset_collate;";

        // 제품 분석 결과 테이블
        $table_name = $wpdb->prefix . 'rocket_sourcer_products';
        $sql .= "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_id varchar(255) NOT NULL,
            title text NOT NULL,
            price int(11) NOT NULL DEFAULT 0,
            original_price int(11) NOT NULL DEFAULT 0,
            category varchar(50) NOT NULL,
            rating float NOT NULL DEFAULT 0,
            review_count int(11) NOT NULL DEFAULT 0,
            seller varchar(255) NOT NULL,
            daily_sales int(11) NOT NULL DEFAULT 0,
            monthly_sales int(11) NOT NULL DEFAULT 0,
            market_share float NOT NULL DEFAULT 0,
            competition_level varchar(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY product_id (product_id),
            KEY category (category)
        ) $charset_collate;";

        // 마진 계산 결과 테이블
        $table_name = $wpdb->prefix . 'rocket_sourcer_margins';
        $sql .= "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_id varchar(255) NOT NULL,
            selling_price int(11) NOT NULL DEFAULT 0,
            cost_price int(11) NOT NULL DEFAULT 0,
            shipping_cost int(11) NOT NULL DEFAULT 0,
            commission int(11) NOT NULL DEFAULT 0,
            additional_costs int(11) NOT NULL DEFAULT 0,
            margin_rate float NOT NULL DEFAULT 0,
            break_even_quantity int(11) NOT NULL DEFAULT 0,
            break_even_amount int(11) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
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