<?php
/**
 * 데이터베이스 작업을 처리하는 클래스
 *
 * @since      1.0.0
 * @package    Rocket_Sourcer
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 데이터베이스 작업을 처리하는 클래스
 *
 * 이 클래스는 플러그인의 데이터베이스 작업을 처리합니다.
 * 제품 데이터 추가, 조회, 수정, 삭제 및 검색 기능을 제공합니다.
 *
 * @since      1.0.0
 * @package    Rocket_Sourcer
 * @author     AI Developer
 */
class Rocket_Sourcer_DB {

    /**
     * 제품 테이블 이름
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $products_table    제품 테이블 이름
     */
    private $products_table;

    /**
     * 검색 기록 테이블 이름
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $searches_table    검색 기록 테이블 이름
     */
    private $searches_table;

    /**
     * 즐겨찾기 테이블 이름
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $favorites_table    즐겨찾기 테이블 이름
     */
    private $favorites_table;

    /**
     * 클래스 생성자
     *
     * 테이블 이름을 초기화합니다.
     *
     * @since    1.0.0
     */
    public function __construct() {
        global $wpdb;
        
        $this->products_table = $wpdb->prefix . 'rocket_sourcer_products';
        $this->searches_table = $wpdb->prefix . 'rocket_sourcer_searches';
        $this->favorites_table = $wpdb->prefix . 'rocket_sourcer_favorites';
    }

    /**
     * 새 제품을 데이터베이스에 추가합니다.
     *
     * @since    1.0.0
     * @param    array    $product_data    제품 데이터
     * @return   int|false                 성공 시 제품 ID, 실패 시 false
     */
    public function add_product($product_data) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->products_table,
            $product_data
        );
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }

    /**
     * 제품 정보를 업데이트합니다.
     *
     * @since    1.0.0
     * @param    int      $product_id      제품 ID
     * @param    array    $product_data    업데이트할 제품 데이터
     * @return   bool                      성공 시 true, 실패 시 false
     */
    public function update_product($product_id, $product_data) {
        global $wpdb;
        
        $result = $wpdb->update(
            $this->products_table,
            $product_data,
            array('id' => $product_id)
        );
        
        return ($result !== false);
    }

    /**
     * 제품을 삭제합니다.
     *
     * @since    1.0.0
     * @param    int      $product_id    제품 ID
     * @return   bool                    성공 시 true, 실패 시 false
     */
    public function delete_product($product_id) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->products_table,
            array('id' => $product_id)
        );
        
        return ($result !== false);
    }

    /**
     * 제품 ID로 제품을 조회합니다.
     *
     * @since    1.0.0
     * @param    int      $product_id    제품 ID
     * @return   object|null             제품 데이터 객체 또는 null
     */
    public function get_product($product_id) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->products_table} WHERE id = %d",
                $product_id
            )
        );
    }

    /**
     * 모든 제품을 조회합니다.
     *
     * @since    1.0.0
     * @param    int      $limit     조회할 제품 수 (기본값: 20)
     * @param    int      $offset    오프셋 (기본값: 0)
     * @param    string   $orderby   정렬 기준 (기본값: id)
     * @param    string   $order     정렬 순서 (기본값: DESC)
     * @return   array               제품 데이터 배열
     */
    public function get_products($limit = 20, $offset = 0, $orderby = 'id', $order = 'DESC') {
        global $wpdb;
        
        // 허용된 정렬 기준 및 순서 확인
        $allowed_orderby = array('id', 'product_name', 'product_price', 'product_rating', 'product_sales', 'product_roi', 'created_at');
        $allowed_order = array('ASC', 'DESC');
        
        if (!in_array($orderby, $allowed_orderby)) {
            $orderby = 'id';
        }
        
        if (!in_array(strtoupper($order), $allowed_order)) {
            $order = 'DESC';
        }
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->products_table} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
                $limit,
                $offset
            )
        );
    }

    /**
     * 제품을 검색합니다.
     *
     * @since    1.0.0
     * @param    string   $search_term    검색어
     * @param    array    $filters        필터 배열
     * @param    int      $limit          조회할 제품 수 (기본값: 20)
     * @param    int      $offset         오프셋 (기본값: 0)
     * @param    string   $orderby        정렬 기준 (기본값: id)
     * @param    string   $order          정렬 순서 (기본값: DESC)
     * @return   array                    제품 데이터 배열
     */
    public function search_products($search_term, $filters = array(), $limit = 20, $offset = 0, $orderby = 'id', $order = 'DESC') {
        global $wpdb;
        
        // 허용된 정렬 기준 및 순서 확인
        $allowed_orderby = array('id', 'product_name', 'product_price', 'product_rating', 'product_sales', 'product_roi', 'created_at');
        $allowed_order = array('ASC', 'DESC');
        
        if (!in_array($orderby, $allowed_orderby)) {
            $orderby = 'id';
        }
        
        if (!in_array(strtoupper($order), $allowed_order)) {
            $order = 'DESC';
        }
        
        // 기본 쿼리 구성
        $sql = "SELECT * FROM {$this->products_table} WHERE 1=1";
        $sql_params = array();
        
        // 검색어 적용
        if (!empty($search_term)) {
            $sql .= " AND (product_name LIKE %s OR product_category LIKE %s OR product_tags LIKE %s)";
            $search_pattern = '%' . $wpdb->esc_like($search_term) . '%';
            $sql_params[] = $search_pattern;
            $sql_params[] = $search_pattern;
            $sql_params[] = $search_pattern;
        }
        
        // 필터 적용
        if (!empty($filters)) {
            // 카테고리 필터
            if (!empty($filters['category'])) {
                $sql .= " AND product_category = %s";
                $sql_params[] = $filters['category'];
            }
            
            // 최소 가격 필터
            if (isset($filters['min_price']) && is_numeric($filters['min_price'])) {
                $sql .= " AND product_price >= %f";
                $sql_params[] = $filters['min_price'];
            }
            
            // 최대 가격 필터
            if (isset($filters['max_price']) && is_numeric($filters['max_price'])) {
                $sql .= " AND product_price <= %f";
                $sql_params[] = $filters['max_price'];
            }
            
            // 최소 평점 필터
            if (isset($filters['min_rating']) && is_numeric($filters['min_rating'])) {
                $sql .= " AND product_rating >= %f";
                $sql_params[] = $filters['min_rating'];
            }
            
            // 최소 ROI 필터
            if (isset($filters['min_roi']) && is_numeric($filters['min_roi'])) {
                $sql .= " AND product_roi >= %f";
                $sql_params[] = $filters['min_roi'];
            }
            
            // 소스 필터
            if (!empty($filters['source'])) {
                $sql .= " AND product_source = %s";
                $sql_params[] = $filters['source'];
            }
        }
        
        // 정렬 및 제한 적용
        $sql .= " ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $sql_params[] = $limit;
        $sql_params[] = $offset;
        
        // 검색 쿼리 실행
        $results = $wpdb->get_results(
            $wpdb->prepare($sql, $sql_params)
        );
        
        // 검색 기록 저장 (로그인한 사용자만)
        if (is_user_logged_in()) {
            $this->add_search_record(get_current_user_id(), $search_term, $filters, count($results));
        }
        
        return $results;
    }

    /**
     * 검색 기록을 저장합니다.
     *
     * @since    1.0.0
     * @param    int      $user_id         사용자 ID
     * @param    string   $search_term     검색어
     * @param    array    $filters         필터 배열
     * @param    int      $search_results  검색 결과 수
     * @return   int|false                 성공 시 기록 ID, 실패 시 false
     */
    public function add_search_record($user_id, $search_term, $filters = array(), $search_results = 0) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->searches_table,
            array(
                'user_id' => $user_id,
                'search_term' => $search_term,
                'search_filters' => !empty($filters) ? json_encode($filters) : null,
                'search_results' => $search_results
            )
        );
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }

    /**
     * 제품을 즐겨찾기에 추가합니다.
     *
     * @since    1.0.0
     * @param    int      $user_id      사용자 ID
     * @param    int      $product_id   제품 ID
     * @param    string   $notes        메모 (선택 사항)
     * @return   int|false              성공 시 즐겨찾기 ID, 실패 시 false
     */
    public function add_to_favorites($user_id, $product_id, $notes = null) {
        global $wpdb;
        
        // 이미 즐겨찾기에 있는지 확인
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$this->favorites_table} WHERE user_id = %d AND product_id = %d",
                $user_id,
                $product_id
            )
        );
        
        if ($existing) {
            // 이미 존재하면 메모만 업데이트
            $result = $wpdb->update(
                $this->favorites_table,
                array('notes' => $notes),
                array('id' => $existing)
            );
            
            return ($result !== false) ? $existing : false;
        } else {
            // 새로 추가
            $result = $wpdb->insert(
                $this->favorites_table,
                array(
                    'user_id' => $user_id,
                    'product_id' => $product_id,
                    'notes' => $notes
                )
            );
            
            if ($result) {
                return $wpdb->insert_id;
            }
            
            return false;
        }
    }

    /**
     * 즐겨찾기에서 제품을 제거합니다.
     *
     * @since    1.0.0
     * @param    int      $user_id      사용자 ID
     * @param    int      $product_id   제품 ID
     * @return   bool                   성공 시 true, 실패 시 false
     */
    public function remove_from_favorites($user_id, $product_id) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->favorites_table,
            array(
                'user_id' => $user_id,
                'product_id' => $product_id
            )
        );
        
        return ($result !== false);
    }

    /**
     * 사용자의 즐겨찾기 제품을 조회합니다.
     *
     * @since    1.0.0
     * @param    int      $user_id    사용자 ID
     * @param    int      $limit      조회할 제품 수 (기본값: 20)
     * @param    int      $offset     오프셋 (기본값: 0)
     * @return   array                즐겨찾기 제품 데이터 배열
     */
    public function get_user_favorites($user_id, $limit = 20, $offset = 0) {
        global $wpdb;
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT f.*, p.* FROM {$this->favorites_table} f
                JOIN {$this->products_table} p ON f.product_id = p.id
                WHERE f.user_id = %d
                ORDER BY f.created_at DESC
                LIMIT %d OFFSET %d",
                $user_id,
                $limit,
                $offset
            )
        );
    }

    /**
     * 제품이 사용자의 즐겨찾기에 있는지 확인합니다.
     *
     * @since    1.0.0
     * @param    int      $user_id      사용자 ID
     * @param    int      $product_id   제품 ID
     * @return   bool                   즐겨찾기에 있으면 true, 없으면 false
     */
    public function is_product_in_favorites($user_id, $product_id) {
        global $wpdb;
        
        $result = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->favorites_table} WHERE user_id = %d AND product_id = %d",
                $user_id,
                $product_id
            )
        );
        
        return ($result > 0);
    }

    /**
     * 제품 총 개수를 반환합니다.
     *
     * @since    1.0.0
     * @return   int    제품 총 개수
     */
    public function get_products_count() {
        global $wpdb;
        
        return $wpdb->get_var("SELECT COUNT(*) FROM {$this->products_table}");
    }

    /**
     * 검색 결과의 총 개수를 반환합니다.
     *
     * @since    1.0.0
     * @param    string   $search_term    검색어
     * @param    array    $filters        필터 배열
     * @return   int                      검색 결과 총 개수
     */
    public function get_search_count($search_term, $filters = array()) {
        global $wpdb;
        
        // 기본 쿼리 구성
        $sql = "SELECT COUNT(*) FROM {$this->products_table} WHERE 1=1";
        $sql_params = array();
        
        // 검색어 적용
        if (!empty($search_term)) {
            $sql .= " AND (product_name LIKE %s OR product_category LIKE %s OR product_tags LIKE %s)";
            $search_pattern = '%' . $wpdb->esc_like($search_term) . '%';
            $sql_params[] = $search_pattern;
            $sql_params[] = $search_pattern;
            $sql_params[] = $search_pattern;
        }
        
        // 필터 적용
        if (!empty($filters)) {
            // 카테고리 필터
            if (!empty($filters['category'])) {
                $sql .= " AND product_category = %s";
                $sql_params[] = $filters['category'];
            }
            
            // 최소 가격 필터
            if (isset($filters['min_price']) && is_numeric($filters['min_price'])) {
                $sql .= " AND product_price >= %f";
                $sql_params[] = $filters['min_price'];
            }
            
            // 최대 가격 필터
            if (isset($filters['max_price']) && is_numeric($filters['max_price'])) {
                $sql .= " AND product_price <= %f";
                $sql_params[] = $filters['max_price'];
            }
            
            // 최소 평점 필터
            if (isset($filters['min_rating']) && is_numeric($filters['min_rating'])) {
                $sql .= " AND product_rating >= %f";
                $sql_params[] = $filters['min_rating'];
            }
            
            // 최소 ROI 필터
            if (isset($filters['min_roi']) && is_numeric($filters['min_roi'])) {
                $sql .= " AND product_roi >= %f";
                $sql_params[] = $filters['min_roi'];
            }
            
            // 소스 필터
            if (!empty($filters['source'])) {
                $sql .= " AND product_source = %s";
                $sql_params[] = $filters['source'];
            }
        }
        
        // 검색 쿼리 실행
        return $wpdb->get_var(
            $wpdb->prepare($sql, $sql_params)
        );
    }

    /**
     * 사용자의 즐겨찾기 총 개수를 반환합니다.
     *
     * @since    1.0.0
     * @param    int      $user_id    사용자 ID
     * @return   int                  즐겨찾기 총 개수
     */
    public function get_user_favorites_count($user_id) {
        global $wpdb;
        
        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->favorites_table} WHERE user_id = %d",
                $user_id
            )
        );
    }
} 