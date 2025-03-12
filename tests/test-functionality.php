<?php
/**
 * Rocket Sourcer 기능 테스트
 */

class Rocket_Sourcer_Functionality_Test extends WP_UnitTestCase {
    private $sourcing;
    private $analyzer;
    private $logger;

    public function setUp(): void {
        parent::setUp();
        $this->sourcing = Rocket_Sourcer_Sourcing::get_instance();
        $this->analyzer = Rocket_Sourcer_Analyzer::get_instance();
        $this->logger = new Rocket_Sourcer_Logger();
    }

    /**
     * 키워드 분석 테스트
     */
    public function test_keyword_analysis() {
        // 기본 키워드 분석 테스트
        $result = $this->analyzer->evaluate_keyword('테스트 키워드');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('volume_score', $result);
        $this->assertArrayHasKey('competition_score', $result);
        $this->assertArrayHasKey('trend_score', $result);

        // 특수 문자 포함 키워드 테스트
        $result = $this->analyzer->evaluate_keyword('테스트!@#$%');
        $this->assertIsArray($result);

        // 빈 키워드 테스트
        $this->expectException(Exception::class);
        $this->analyzer->evaluate_keyword('');
    }

    /**
     * 제품 분석 테스트
     */
    public function test_product_analysis() {
        // 유효한 제품 URL 테스트
        $result = $this->analyzer->analyze_product('https://test.com/product/1');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('basic_info', $result);
        $this->assertArrayHasKey('profitability', $result);

        // 잘못된 URL 테스트
        $this->expectException(Exception::class);
        $this->analyzer->analyze_product('invalid-url');
    }

    /**
     * 마진 계산 테스트
     */
    public function test_margin_calculation() {
        $data = [
            'product_cost' => 10000,
            'selling_price' => 20000,
            'shipping_cost' => 2500,
            'coupang_fee_rate' => 10
        ];

        $result = $this->analyzer->calculate_margin($data);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('revenue', $result);
        $this->assertArrayHasKey('total_cost', $result);
        $this->assertArrayHasKey('net_profit', $result);
        
        // 손익분기점 확인
        $this->assertTrue($result['net_profit'] > 0);
        
        // 잘못된 입력값 테스트
        $this->expectException(Exception::class);
        $this->analyzer->calculate_margin(['product_cost' => -1000]);
    }

    /**
     * 데이터베이스 저장 및 조회 테스트
     */
    public function test_database_operations() {
        global $wpdb;
        
        // 키워드 분석 결과 저장
        $keyword_data = [
            'keyword' => '테스트 키워드',
            'volume_score' => 80,
            'competition_score' => 60,
            'trend_score' => 70
        ];
        
        $saved = $this->analyzer->save_keyword_analysis($keyword_data);
        $this->assertTrue($saved);
        
        // 저장된 데이터 조회
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}rocket_sourcer_keywords WHERE keyword = %s",
                $keyword_data['keyword']
            )
        );
        
        $this->assertNotNull($result);
        $this->assertEquals($keyword_data['volume_score'], $result->volume_score);
    }

    /**
     * API 연동 테스트
     */
    public function test_api_integration() {
        // 1688 검색 테스트
        $result = $this->sourcing->search_1688('test product');
        $this->assertIsArray($result);
        
        // AliExpress 검색 테스트
        $result = $this->sourcing->search_aliexpress('test product');
        $this->assertIsArray($result);
        
        // 이미지 검색 테스트
        $result = $this->sourcing->search_by_image('https://test.com/image.jpg');
        $this->assertIsArray($result);
    }

    /**
     * 캐시 기능 테스트
     */
    public function test_caching() {
        $keyword = '캐시 테스트 키워드';
        
        // 첫 번째 분석 (캐시 없음)
        $start = microtime(true);
        $result1 = $this->analyzer->evaluate_keyword($keyword);
        $time1 = microtime(true) - $start;
        
        // 두 번째 분석 (캐시 사용)
        $start = microtime(true);
        $result2 = $this->analyzer->evaluate_keyword($keyword);
        $time2 = microtime(true) - $start;
        
        // 결과가 동일한지 확인
        $this->assertEquals($result1, $result2);
        
        // 캐시된 요청이 더 빠른지 확인
        $this->assertLessThan($time1, $time2);
    }

    public function tearDown(): void {
        parent::tearDown();
    }
} 