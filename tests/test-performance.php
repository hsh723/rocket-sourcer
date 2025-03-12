<?php
/**
 * Rocket Sourcer 성능 테스트
 */

class Rocket_Sourcer_Performance_Test extends WP_UnitTestCase {
    private $sourcing;
    private $analyzer;
    private $logger;
    private $start_memory;
    private $start_time;

    public function setUp(): void {
        parent::setUp();
        $this->sourcing = Rocket_Sourcer_Sourcing::get_instance();
        $this->analyzer = Rocket_Sourcer_Analyzer::get_instance();
        $this->logger = new Rocket_Sourcer_Logger();
        
        // 초기 메모리 사용량 및 시간 기록
        $this->start_memory = memory_get_usage();
        $this->start_time = microtime(true);
    }

    /**
     * 대량 키워드 분석 성능 테스트
     */
    public function test_bulk_keyword_analysis() {
        $keywords = $this->generate_test_keywords(100);
        $results = [];
        $times = [];
        $memory_usage = [];

        foreach ($keywords as $keyword) {
            $start = microtime(true);
            $memory_before = memory_get_usage();
            
            $result = $this->analyzer->evaluate_keyword($keyword);
            
            $times[] = microtime(true) - $start;
            $memory_usage[] = memory_get_usage() - $memory_before;
            $results[] = $result;
        }

        // 성능 메트릭 계산
        $avg_time = array_sum($times) / count($times);
        $avg_memory = array_sum($memory_usage) / count($memory_usage);
        $max_memory = max($memory_usage);

        // 성능 기준 확인
        $this->assertLessThan(0.5, $avg_time, '평균 처리 시간이 0.5초를 초과합니다.');
        $this->assertLessThan(1024 * 1024, $avg_memory, '평균 메모리 사용량이 1MB를 초과합니다.');
        
        $this->logger->log_info(sprintf(
            '벌크 키워드 분석 성능: 평균 시간 %.3f초, 평균 메모리 %.2fKB, 최대 메모리 %.2fKB',
            $avg_time,
            $avg_memory / 1024,
            $max_memory / 1024
        ));
    }

    /**
     * 동시 요청 처리 성능 테스트
     */
    public function test_concurrent_requests() {
        $concurrent_requests = 10;
        $results = [];
        
        // 멀티스레드 테스트를 위한 curl 멀티 핸들 사용
        $mh = curl_multi_init();
        $curl_handles = [];
        
        // 동시 요청 준비
        for ($i = 0; $i < $concurrent_requests; $i++) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, home_url('/wp-json/rocket-sourcer/v1/analyze-keyword'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'keyword' => "테스트 키워드 {$i}",
                'category' => 'test'
            ]);
            
            curl_multi_add_handle($mh, $ch);
            $curl_handles[] = $ch;
        }

        // 동시 요청 실행
        $running = null;
        do {
            curl_multi_exec($mh, $running);
        } while ($running);

        // 결과 수집
        foreach ($curl_handles as $ch) {
            $results[] = curl_multi_getcontent($ch);
            curl_multi_remove_handle($mh, $ch);
        }
        
        curl_multi_close($mh);

        // 모든 요청이 성공적으로 처리되었는지 확인
        $this->assertEquals($concurrent_requests, count($results));
        
        $this->logger->log_info(sprintf(
            '동시 요청 처리 완료: %d개 요청',
            $concurrent_requests
        ));
    }

    /**
     * 데이터베이스 성능 테스트
     */
    public function test_database_performance() {
        global $wpdb;
        
        $batch_size = 1000;
        $start_time = microtime(true);
        
        // 대량 데이터 삽입 테스트
        $values = [];
        for ($i = 0; $i < $batch_size; $i++) {
            $values[] = $wpdb->prepare(
                '(%s, %d, %d, %d, %s)',
                "테스트 키워드 {$i}",
                rand(1, 100),
                rand(1, 100),
                rand(1, 100),
                current_time('mysql')
            );
        }

        $query = "INSERT INTO {$wpdb->prefix}rocket_sourcer_keywords 
                 (keyword, volume_score, competition_score, trend_score, created_at) 
                 VALUES " . implode(',', $values);
        
        $wpdb->query($query);
        
        $insert_time = microtime(true) - $start_time;
        
        // 대량 데이터 조회 테스트
        $start_time = microtime(true);
        $results = $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}rocket_sourcer_keywords 
            ORDER BY volume_score DESC 
            LIMIT 100
        ");
        
        $select_time = microtime(true) - $start_time;
        
        // 성능 기준 확인
        $this->assertLessThan(5, $insert_time, '대량 삽입 시간이 5초를 초과합니다.');
        $this->assertLessThan(1, $select_time, '대량 조회 시간이 1초를 초과합니다.');
        
        $this->logger->log_info(sprintf(
            'DB 성능: 삽입 시간 %.3f초 (%d 레코드), 조회 시간 %.3f초',
            $insert_time,
            $batch_size,
            $select_time
        ));
    }

    /**
     * 메모리 누수 테스트
     */
    public function test_memory_leaks() {
        $iterations = 100;
        $memory_usage = [];
        
        for ($i = 0; $i < $iterations; $i++) {
            $memory_before = memory_get_usage();
            
            // 메모리 사용이 많은 작업 수행
            $this->analyzer->evaluate_keyword("메모리 테스트 키워드 {$i}");
            $this->analyzer->analyze_product("https://test.com/product/{$i}");
            
            $memory_after = memory_get_usage();
            $memory_usage[] = $memory_after - $memory_before;
            
            // 가비지 컬렉션 강제 실행
            gc_collect_cycles();
        }
        
        // 메모리 사용량이 지속적으로 증가하지 않는지 확인
        $trend = $this->calculate_memory_trend($memory_usage);
        
        $this->assertLessThan(
            0.1,
            $trend,
            '메모리 사용량이 지속적으로 증가하고 있습니다.'
        );
    }

    /**
     * 응답 시간 분포 테스트
     */
    public function test_response_time_distribution() {
        $samples = 50;
        $response_times = [];
        
        for ($i = 0; $i < $samples; $i++) {
            $start = microtime(true);
            
            // 다양한 API 엔드포인트 테스트
            $this->sourcing->search_1688('test');
            $this->sourcing->search_aliexpress('test');
            $this->analyzer->evaluate_keyword('test');
            
            $response_times[] = microtime(true) - $start;
        }
        
        // 응답 시간 통계 계산
        $avg_time = array_sum($response_times) / count($response_times);
        $max_time = max($response_times);
        $min_time = min($response_times);
        $percentile_95 = $this->calculate_percentile($response_times, 95);
        
        // 성능 기준 확인
        $this->assertLessThan(1, $avg_time, '평균 응답 시간이 1초를 초과합니다.');
        $this->assertLessThan(2, $percentile_95, '95 퍼센타일 응답 시간이 2초를 초과합니다.');
        
        $this->logger->log_info(sprintf(
            '응답 시간 분포: 평균 %.3f초, 최소 %.3f초, 최대 %.3f초, 95%% %.3f초',
            $avg_time,
            $min_time,
            $max_time,
            $percentile_95
        ));
    }

    /**
     * 테스트 키워드 생성
     */
    private function generate_test_keywords($count) {
        $keywords = [];
        $base_keywords = ['의류', '전자제품', '식품', '가구', '화장품'];
        
        for ($i = 0; $i < $count; $i++) {
            $base = $base_keywords[array_rand($base_keywords)];
            $keywords[] = "{$base} 테스트 상품 {$i}";
        }
        
        return $keywords;
    }

    /**
     * 메모리 사용량 추세 계산
     */
    private function calculate_memory_trend($memory_usage) {
        $n = count($memory_usage);
        $sum_x = array_sum(range(1, $n));
        $sum_y = array_sum($memory_usage);
        $sum_xy = 0;
        $sum_xx = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $x = $i + 1;
            $sum_xy += $x * $memory_usage[$i];
            $sum_xx += $x * $x;
        }
        
        // 선형 회귀 기울기 계산
        $slope = ($n * $sum_xy - $sum_x * $sum_y) / ($n * $sum_xx - $sum_x * $sum_x);
        
        return $slope;
    }

    /**
     * 백분위수 계산
     */
    private function calculate_percentile($data, $percentile) {
        sort($data);
        $index = ceil(($percentile / 100) * count($data)) - 1;
        return $data[$index];
    }

    public function tearDown(): void {
        // 메모리 사용량 및 실행 시간 로깅
        $memory_used = memory_get_usage() - $this->start_memory;
        $time_taken = microtime(true) - $this->start_time;
        
        $this->logger->log_info(sprintf(
            '테스트 완료: 메모리 사용 %.2fMB, 실행 시간 %.3f초',
            $memory_used / 1024 / 1024,
            $time_taken
        ));
        
        parent::tearDown();
    }
} 