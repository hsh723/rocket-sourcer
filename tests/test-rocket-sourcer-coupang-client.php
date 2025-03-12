<?php
/**
 * 쿠팡 API 클라이언트 테스트
 *
 * @package    Rocket_Sourcer
 * @subpackage Rocket_Sourcer/tests
 */

class Test_Rocket_Sourcer_Coupang_Client extends WP_UnitTestCase {

    /**
     * 테스트할 클래스 인스턴스
     */
    private $client;

    /**
     * 테스트 설정
     */
    public function setUp(): void {
        parent::setUp();
        $this->client = new Rocket_Sourcer_Coupang_Client();
    }

    /**
     * API 상태 확인 테스트
     */
    public function test_check_api_status() {
        // API 키가 없는 경우
        $this->assertFalse($this->client->check_api_status());

        // API 키 설정
        update_option('rocket_sourcer_api_key', 'test_key');
        update_option('rocket_sourcer_api_secret', 'test_secret');

        // API 키가 있는 경우
        $this->assertTrue($this->client->check_api_status());
    }

    /**
     * 인기 키워드 가져오기 테스트
     */
    public function test_get_popular_keywords() {
        $result = $this->client->get_popular_keywords('fashion');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('keywords', $result);
        $this->assertNotEmpty($result['keywords']);

        foreach ($result['keywords'] as $keyword) {
            $this->assertArrayHasKey('keyword', $keyword);
            $this->assertArrayHasKey('volume', $keyword);
            $this->assertArrayHasKey('competition', $keyword);
            $this->assertArrayHasKey('trend', $keyword);
            $this->assertArrayHasKey('trend_percentage', $keyword);
        }
    }

    /**
     * 키워드 분석 테스트
     */
    public function test_analyze_keyword() {
        $result = $this->client->analyze_keyword('테스트 키워드');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('keyword', $result);
        $this->assertArrayHasKey('total_volume', $result);
        $this->assertArrayHasKey('average_volume', $result);
        $this->assertArrayHasKey('competition_level', $result);
        $this->assertArrayHasKey('trend_direction', $result);
        $this->assertArrayHasKey('trend_percentage', $result);
        $this->assertArrayHasKey('estimated_cpc', $result);
        $this->assertArrayHasKey('monthly_volumes', $result);
        $this->assertArrayHasKey('related_keywords', $result);

        $this->assertIsArray($result['monthly_volumes']);
        $this->assertIsArray($result['related_keywords']);
    }

    /**
     * 제품 검색 테스트
     */
    public function test_search_products() {
        $result = $this->client->search_products('테스트 제품');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('products', $result);
        $this->assertNotEmpty($result['products']);

        foreach ($result['products'] as $product) {
            $this->assertArrayHasKey('id', $product);
            $this->assertArrayHasKey('title', $product);
            $this->assertArrayHasKey('price', $product);
            $this->assertArrayHasKey('original_price', $product);
            $this->assertArrayHasKey('category', $product);
            $this->assertArrayHasKey('rating', $product);
            $this->assertArrayHasKey('review_count', $product);
            $this->assertArrayHasKey('seller', $product);
            $this->assertArrayHasKey('image_url', $product);
        }
    }

    /**
     * 제품 분석 테스트
     */
    public function test_analyze_product() {
        $result = $this->client->analyze_product('TEST123');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('product_id', $result);
        $this->assertArrayHasKey('daily_sales', $result);
        $this->assertArrayHasKey('monthly_sales', $result);
        $this->assertArrayHasKey('market_share', $result);
        $this->assertArrayHasKey('competition_level', $result);
        $this->assertArrayHasKey('price_history', $result);
        $this->assertArrayHasKey('review_analysis', $result);

        $this->assertIsArray($result['price_history']);
        $this->assertIsArray($result['review_analysis']);
    }

    /**
     * 테스트 정리
     */
    public function tearDown(): void {
        delete_option('rocket_sourcer_api_key');
        delete_option('rocket_sourcer_api_secret');
        parent::tearDown();
    }
} 