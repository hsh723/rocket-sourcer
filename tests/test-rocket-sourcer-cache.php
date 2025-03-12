<?php
/**
 * 캐시 시스템 테스트
 *
 * @package    Rocket_Sourcer
 * @subpackage Rocket_Sourcer/tests
 */

class Test_Rocket_Sourcer_Cache extends WP_UnitTestCase {

    /**
     * 테스트할 클래스 인스턴스
     */
    private $cache;

    /**
     * 테스트 설정
     */
    public function setUp(): void {
        parent::setUp();
        $this->cache = new Rocket_Sourcer_Cache();
    }

    /**
     * 기본 캐시 기능 테스트
     */
    public function test_basic_cache_operations() {
        $type = 'test';
        $identifier = 'test_id';
        $data = array('test' => 'data');

        // 캐시 저장
        $this->assertTrue($this->cache->set($type, $identifier, $data));

        // 캐시 조회
        $cached_data = $this->cache->get($type, $identifier);
        $this->assertEquals($data, $cached_data);

        // 캐시 삭제
        $this->assertTrue($this->cache->delete($type, $identifier));
        $this->assertFalse($this->cache->get($type, $identifier));
    }

    /**
     * 키워드 분석 캐시 테스트
     */
    public function test_keyword_analysis_cache() {
        $keyword = '테스트 키워드';
        $data = array(
            'keyword' => $keyword,
            'volume' => 1000,
            'competition' => 'HIGH'
        );

        // 캐시 저장
        $this->assertTrue($this->cache->set_keyword_analysis($keyword, $data));

        // 캐시 조회
        $cached_data = $this->cache->get_keyword_analysis($keyword);
        $this->assertEquals($data, $cached_data);
    }

    /**
     * 제품 분석 캐시 테스트
     */
    public function test_product_analysis_cache() {
        $product_id = 'TEST123';
        $data = array(
            'product_id' => $product_id,
            'daily_sales' => 100,
            'monthly_sales' => 3000
        );

        // 캐시 저장
        $this->assertTrue($this->cache->set_product_analysis($product_id, $data));

        // 캐시 조회
        $cached_data = $this->cache->get_product_analysis($product_id);
        $this->assertEquals($data, $cached_data);
    }

    /**
     * 제품 검색 캐시 테스트
     */
    public function test_product_search_cache() {
        $keyword = '테스트 제품';
        $data = array(
            'products' => array(
                array('id' => 1, 'title' => '제품 1'),
                array('id' => 2, 'title' => '제품 2')
            )
        );

        // 캐시 저장
        $this->assertTrue($this->cache->set_product_search($keyword, $data));

        // 캐시 조회
        $cached_data = $this->cache->get_product_search($keyword);
        $this->assertEquals($data, $cached_data);
    }

    /**
     * 인기 키워드 캐시 테스트
     */
    public function test_popular_keywords_cache() {
        $category = 'fashion';
        $data = array(
            'keywords' => array(
                array('keyword' => '키워드 1', 'volume' => 1000),
                array('keyword' => '키워드 2', 'volume' => 800)
            )
        );

        // 캐시 저장
        $this->assertTrue($this->cache->set_popular_keywords($category, $data));

        // 캐시 조회
        $cached_data = $this->cache->get_popular_keywords($category);
        $this->assertEquals($data, $cached_data);
    }

    /**
     * 마진 계산 캐시 테스트
     */
    public function test_margin_calculation_cache() {
        $identifier = 'TEST123_MARGIN';
        $data = array(
            'selling_price' => 10000,
            'cost_price' => 5000,
            'margin_rate' => 50
        );

        // 캐시 저장
        $this->assertTrue($this->cache->set_margin_calculation($identifier, $data));

        // 캐시 조회
        $cached_data = $this->cache->get_margin_calculation($identifier);
        $this->assertEquals($data, $cached_data);
    }

    /**
     * 캐시 만료 테스트
     */
    public function test_cache_expiration() {
        $type = 'test';
        $identifier = 'expiration_test';
        $data = array('test' => 'data');

        // 1초 만료 시간으로 캐시 저장
        $this->assertTrue($this->cache->set($type, $identifier, $data, 1));

        // 캐시가 있는지 확인
        $this->assertEquals($data, $this->cache->get($type, $identifier));

        // 2초 대기
        sleep(2);

        // 캐시가 만료되었는지 확인
        $this->assertFalse($this->cache->get($type, $identifier));
    }

    /**
     * 캐시 플러시 테스트
     */
    public function test_cache_flush() {
        $type = 'test';
        $identifier = 'flush_test';
        $data = array('test' => 'data');

        // 캐시 저장
        $this->assertTrue($this->cache->set($type, $identifier, $data));

        // 캐시 플러시
        $this->assertTrue($this->cache->flush());

        // 캐시가 삭제되었는지 확인
        $this->assertFalse($this->cache->get($type, $identifier));
    }

    /**
     * 테스트 정리
     */
    public function tearDown(): void {
        wp_cache_flush();
        parent::tearDown();
    }
} 