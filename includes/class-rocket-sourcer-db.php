<?php
/**
 * 데이터베이스 관리 클래스
 *
 * @package    Rocket_Sourcer
 * @subpackage Rocket_Sourcer/includes
 */

class Rocket_Sourcer_DB {

    /**
     * 키워드 분석 결과 저장
     *
     * @param array $data 저장할 데이터
     * @return int|false 저장된 데이터의 ID 또는 실패 시 false
     */
    public static function save_keyword_analysis($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'rocket_sourcer_keywords';

        $result = $wpdb->insert(
            $table,
            array(
                'keyword' => $data['keyword'],
                'category' => $data['category'],
                'volume' => $data['volume'],
                'competition' => $data['competition'],
                'trend' => $data['trend'],
                'trend_percentage' => $data['trend_percentage'],
                'estimated_cpc' => $data['estimated_cpc']
            ),
            array('%s', '%s', '%d', '%s', '%s', '%d', '%d')
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * 제품 분석 결과 저장
     *
     * @param array $data 저장할 데이터
     * @return int|false 저장된 데이터의 ID 또는 실패 시 false
     */
    public static function save_product_analysis($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'rocket_sourcer_products';

        $result = $wpdb->insert(
            $table,
            array(
                'product_id' => $data['product_id'],
                'title' => $data['title'],
                'price' => $data['price'],
                'original_price' => $data['original_price'],
                'category' => $data['category'],
                'rating' => $data['rating'],
                'review_count' => $data['review_count'],
                'seller' => $data['seller'],
                'daily_sales' => $data['daily_sales'],
                'monthly_sales' => $data['monthly_sales'],
                'market_share' => $data['market_share'],
                'competition_level' => $data['competition_level']
            ),
            array('%s', '%s', '%d', '%d', '%s', '%f', '%d', '%s', '%d', '%d', '%f', '%s')
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * 마진 계산 결과 저장
     *
     * @param array $data 저장할 데이터
     * @return int|false 저장된 데이터의 ID 또는 실패 시 false
     */
    public static function save_margin_calculation($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'rocket_sourcer_margins';

        $result = $wpdb->insert(
            $table,
            array(
                'product_id' => $data['product_id'],
                'selling_price' => $data['selling_price'],
                'cost_price' => $data['cost_price'],
                'shipping_cost' => $data['shipping_cost'],
                'commission' => $data['commission'],
                'additional_costs' => $data['additional_costs'],
                'margin_rate' => $data['margin_rate'],
                'break_even_quantity' => $data['break_even_quantity'],
                'break_even_amount' => $data['break_even_amount']
            ),
            array('%s', '%d', '%d', '%d', '%d', '%d', '%f', '%d', '%d')
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * 검색 기록 저장
     *
     * @param array $data 저장할 데이터
     * @return int|false 저장된 데이터의 ID 또는 실패 시 false
     */
    public static function save_search_history($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'rocket_sourcer_searches';

        $result = $wpdb->insert(
            $table,
            array(
                'user_id' => $data['user_id'],
                'search_type' => $data['search_type'],
                'keyword' => $data['keyword']
            ),
            array('%d', '%s', '%s')
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * 키워드 분석 결과 조회
     *
     * @param string $keyword 검색할 키워드
     * @return array|null 분석 결과 또는 null
     */
    public static function get_keyword_analysis($keyword) {
        global $wpdb;
        $table = $wpdb->prefix . 'rocket_sourcer_keywords';

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE keyword = %s ORDER BY created_at DESC LIMIT 1",
                $keyword
            ),
            ARRAY_A
        );
    }

    /**
     * 제품 분석 결과 조회
     *
     * @param string $product_id 제품 ID
     * @return array|null 분석 결과 또는 null
     */
    public static function get_product_analysis($product_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'rocket_sourcer_products';

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE product_id = %s",
                $product_id
            ),
            ARRAY_A
        );
    }

    /**
     * 마진 계산 결과 조회
     *
     * @param string $product_id 제품 ID
     * @return array|null 계산 결과 또는 null
     */
    public static function get_margin_calculation($product_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'rocket_sourcer_margins';

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE product_id = %s ORDER BY created_at DESC LIMIT 1",
                $product_id
            ),
            ARRAY_A
        );
    }

    /**
     * 오래된 데이터 삭제
     *
     * @param int $days 보관 기간 (일)
     * @return bool 삭제 성공 여부
     */
    public static function cleanup_old_data($days) {
        global $wpdb;
        $tables = array(
            $wpdb->prefix . 'rocket_sourcer_keywords',
            $wpdb->prefix . 'rocket_sourcer_products',
            $wpdb->prefix . 'rocket_sourcer_margins',
            $wpdb->prefix . 'rocket_sourcer_searches'
        );

        $success = true;
        foreach ($tables as $table) {
            $result = $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM $table WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                    $days
                )
            );
            if ($result === false) {
                $success = false;
            }
        }

        return $success;
    }
} 