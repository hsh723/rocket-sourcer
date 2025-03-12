<?php
/**
 * REST API 처리 클래스
 *
 * @package    Rocket_Sourcer
 * @subpackage Rocket_Sourcer/includes
 */

class Rocket_Sourcer_API {

    /**
     * API 네임스페이스
     */
    private $namespace = 'rocket-sourcer/v1';

    /**
     * 키워드 분석 클래스 인스턴스
     */
    private $keywords;

    /**
     * 제품 분석 클래스 인스턴스
     */
    private $products;

    /**
     * 마진 계산 클래스 인스턴스
     */
    private $calculator;

    /**
     * 생성자
     */
    public function __construct() {
        $this->keywords = new Rocket_Sourcer_Keywords();
        $this->products = new Rocket_Sourcer_Products();
        $this->calculator = new Rocket_Sourcer_Calculator();
    }

    /**
     * API 엔드포인트 등록
     */
    public function register_routes() {
        // 키워드 분석 엔드포인트
        register_rest_route($this->namespace, '/keywords/popular/(?P<category>[a-zA-Z0-9-]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_popular_keywords'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'category' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));

        register_rest_route($this->namespace, '/keywords/analyze', array(
            'methods' => 'POST',
            'callback' => array($this, 'analyze_keyword'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'keyword' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));

        // 제품 분석 엔드포인트
        register_rest_route($this->namespace, '/products/search', array(
            'methods' => 'POST',
            'callback' => array($this, 'search_products'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'keyword' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'sort' => array(
                    'type' => 'string',
                    'default' => 'relevance',
                    'enum' => array('relevance', 'price_asc', 'price_desc', 'rating', 'review_count')
                )
            )
        ));

        register_rest_route($this->namespace, '/products/analyze/(?P<product_id>[a-zA-Z0-9-]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'analyze_product'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'product_id' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));

        // 마진 계산 엔드포인트
        register_rest_route($this->namespace, '/calculator/margin', array(
            'methods' => 'POST',
            'callback' => array($this, 'calculate_margin'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'selling_price' => array(
                    'required' => true,
                    'type' => 'integer',
                    'minimum' => 0
                ),
                'cost_price' => array(
                    'required' => true,
                    'type' => 'integer',
                    'minimum' => 0
                ),
                'category' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));

        register_rest_route($this->namespace, '/calculator/break-even', array(
            'methods' => 'POST',
            'callback' => array($this, 'calculate_break_even'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'fixed_costs' => array(
                    'required' => true,
                    'type' => 'integer',
                    'minimum' => 0
                ),
                'cost_price' => array(
                    'required' => true,
                    'type' => 'integer',
                    'minimum' => 0
                ),
                'selling_price' => array(
                    'required' => true,
                    'type' => 'integer',
                    'minimum' => 0
                ),
                'category' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));
    }

    /**
     * 권한 확인
     *
     * @return bool 권한 여부
     */
    public function check_permission() {
        return current_user_can('manage_options');
    }

    /**
     * 인기 키워드 가져오기
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_popular_keywords($request) {
        $category = $request->get_param('category');
        $keywords = $this->keywords->get_popular_keywords($category);

        // 검색 기록 저장
        if (!empty($keywords)) {
            Rocket_Sourcer_DB::save_search_history(array(
                'user_id' => get_current_user_id(),
                'search_type' => 'popular_keywords',
                'keyword' => $category
            ));
        }

        return rest_ensure_response($keywords);
    }

    /**
     * 키워드 분석하기
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function analyze_keyword($request) {
        $keyword = $request->get_param('keyword');
        
        // 캐시된 결과 확인
        $cached_result = Rocket_Sourcer_DB::get_keyword_analysis($keyword);
        if ($cached_result) {
            return rest_ensure_response($cached_result);
        }

        // 새로운 분석 실행
        $result = $this->keywords->analyze_keyword($keyword);

        // 결과 저장
        if ($result) {
            Rocket_Sourcer_DB::save_keyword_analysis($result);
            Rocket_Sourcer_DB::save_search_history(array(
                'user_id' => get_current_user_id(),
                'search_type' => 'keyword_analysis',
                'keyword' => $keyword
            ));
        }

        return rest_ensure_response($result);
    }

    /**
     * 제품 검색하기
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function search_products($request) {
        $keyword = $request->get_param('keyword');
        $options = array(
            'sort' => $request->get_param('sort')
        );

        $products = $this->products->search_products($keyword, $options);

        // 검색 기록 저장
        if (!empty($products)) {
            Rocket_Sourcer_DB::save_search_history(array(
                'user_id' => get_current_user_id(),
                'search_type' => 'product_search',
                'keyword' => $keyword
            ));
        }

        return rest_ensure_response($products);
    }

    /**
     * 제품 분석하기
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function analyze_product($request) {
        $product_id = $request->get_param('product_id');

        // 캐시된 결과 확인
        $cached_result = Rocket_Sourcer_DB::get_product_analysis($product_id);
        if ($cached_result) {
            return rest_ensure_response($cached_result);
        }

        // 새로운 분석 실행
        $result = $this->products->analyze_product($product_id);

        // 결과 저장
        if ($result) {
            Rocket_Sourcer_DB::save_product_analysis($result);
            Rocket_Sourcer_DB::save_search_history(array(
                'user_id' => get_current_user_id(),
                'search_type' => 'product_analysis',
                'keyword' => $product_id
            ));
        }

        return rest_ensure_response($result);
    }

    /**
     * 마진 계산하기
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function calculate_margin($request) {
        $data = array(
            'selling_price' => $request->get_param('selling_price'),
            'cost_price' => $request->get_param('cost_price'),
            'category' => $request->get_param('category'),
            'shipping_type' => $request->get_param('shipping_type', 'basic'),
            'is_free_shipping' => $request->get_param('is_free_shipping', false),
            'additional_costs' => $request->get_param('additional_costs', 0)
        );

        try {
            $result = $this->calculator->calculate_margin($data);

            // 결과 저장 (제품 ID가 있는 경우)
            if (!empty($request->get_param('product_id'))) {
                $margin_data = array_merge(
                    array('product_id' => $request->get_param('product_id')),
                    $data,
                    array('margin_rate' => $result['summary']['margin_rate'])
                );
                Rocket_Sourcer_DB::save_margin_calculation($margin_data);
            }

            return rest_ensure_response($result);
        } catch (Exception $e) {
            return new WP_Error('calculation_error', $e->getMessage(), array('status' => 400));
        }
    }

    /**
     * 손익분기점 계산하기
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function calculate_break_even($request) {
        $data = array(
            'fixed_costs' => $request->get_param('fixed_costs'),
            'cost_price' => $request->get_param('cost_price'),
            'selling_price' => $request->get_param('selling_price'),
            'category' => $request->get_param('category'),
            'shipping_type' => $request->get_param('shipping_type', 'basic'),
            'is_free_shipping' => $request->get_param('is_free_shipping', false),
            'additional_costs' => $request->get_param('additional_costs', 0)
        );

        try {
            $result = $this->calculator->calculate_break_even($data);

            // 결과 저장 (제품 ID가 있는 경우)
            if (!empty($request->get_param('product_id'))) {
                $break_even_data = array_merge(
                    array('product_id' => $request->get_param('product_id')),
                    $data,
                    array(
                        'break_even_quantity' => $result['break_even_point']['quantity'],
                        'break_even_amount' => $result['break_even_point']['amount']
                    )
                );
                Rocket_Sourcer_DB::save_margin_calculation($break_even_data);
            }

            return rest_ensure_response($result);
        } catch (Exception $e) {
            return new WP_Error('calculation_error', $e->getMessage(), array('status' => 400));
        }
    }
} 