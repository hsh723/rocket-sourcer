<?php
/**
 * 쿠팡 데이터 크롤링 클래스
 *
 * @package RocketSourcer
 */

class Rocket_Sourcer_Crawler {
    /**
     * 인스턴스
     *
     * @var Rocket_Sourcer_Crawler
     */
    private static $instance = null;

    /**
     * 마지막 요청 시간
     *
     * @var int
     */
    private $last_request_time = 0;

    /**
     * 요청 간격 (초)
     *
     * @var int
     */
    private $request_interval = 2;

    /**
     * 캐시 만료 시간 (초)
     *
     * @var int
     */
    private $cache_expiration = 3600;

    /**
     * 싱글톤 인스턴스 반환
     *
     * @return Rocket_Sourcer_Crawler
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 생성자
     */
    private function __construct() {
        // 캐시 초기화
        if (!wp_cache_get('rocket_sourcer_crawler_cache')) {
            wp_cache_set('rocket_sourcer_crawler_cache', array(), '', $this->cache_expiration);
        }
    }

    /**
     * 키워드로 검색 결과 수집
     *
     * @param string $keyword 검색 키워드
     * @param string $category 카테고리
     * @return array 검색 결과 데이터
     */
    public function analyze_keyword($keyword, $category = '') {
        $cache_key = 'keyword_' . md5($keyword . $category);
        $cached_data = $this->get_cache($cache_key);

        if ($cached_data) {
            return $cached_data;
        }

        try {
            $search_url = $this->build_search_url($keyword, $category);
            $response = $this->make_request($search_url);

            if (!$response) {
                throw new Exception('검색 결과를 가져올 수 없습니다.');
            }

            $data = $this->parse_search_results($response);
            $data['competition'] = $this->analyze_competition($data['products']);
            $data['trend'] = $this->analyze_trend($keyword);

            $this->set_cache($cache_key, $data);
            return $data;

        } catch (Exception $e) {
            $this->log_error('키워드 분석 오류: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 제품 상세 정보 수집
     *
     * @param string $product_id 제품 ID
     * @return array 제품 상세 정보
     */
    public function get_product_details($product_id) {
        $cache_key = 'product_' . $product_id;
        $cached_data = $this->get_cache($cache_key);

        if ($cached_data) {
            return $cached_data;
        }

        try {
            $product_url = $this->build_product_url($product_id);
            $response = $this->make_request($product_url);

            if (!$response) {
                throw new Exception('제품 정보를 가져올 수 없습니다.');
            }

            $data = $this->parse_product_details($response);
            $data['reviews'] = $this->get_product_reviews($product_id);

            $this->set_cache($cache_key, $data);
            return $data;

        } catch (Exception $e) {
            $this->log_error('제품 정보 수집 오류: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 제품 리뷰 수집
     *
     * @param string $product_id 제품 ID
     * @param int $page 페이지 번호
     * @return array 리뷰 데이터
     */
    public function get_product_reviews($product_id, $page = 1) {
        $cache_key = 'reviews_' . $product_id . '_' . $page;
        $cached_data = $this->get_cache($cache_key);

        if ($cached_data) {
            return $cached_data;
        }

        try {
            $reviews_url = $this->build_reviews_url($product_id, $page);
            $response = $this->make_request($reviews_url);

            if (!$response) {
                throw new Exception('리뷰를 가져올 수 없습니다.');
            }

            $reviews = $this->parse_reviews($response);
            $sentiment = $this->analyze_review_sentiment($reviews);
            $keywords = $this->extract_review_keywords($reviews);

            $data = array(
                'reviews' => $reviews,
                'sentiment' => $sentiment,
                'keywords' => $keywords
            );

            $this->set_cache($cache_key, $data);
            return $data;

        } catch (Exception $e) {
            $this->log_error('리뷰 수집 오류: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 경쟁 분석
     *
     * @param array $products 제품 목록
     * @return array 경쟁 분석 데이터
     */
    private function analyze_competition($products) {
        $total_products = count($products);
        if ($total_products === 0) {
            return array(
                'level' => 'low',
                'score' => 0
            );
        }

        $price_points = array_column($products, 'price');
        $review_counts = array_column($products, 'review_count');
        $ratings = array_column($products, 'rating');

        $avg_price = array_sum($price_points) / $total_products;
        $avg_reviews = array_sum($review_counts) / $total_products;
        $avg_rating = array_sum($ratings) / $total_products;

        $price_std = $this->calculate_std_deviation($price_points);
        $review_std = $this->calculate_std_deviation($review_counts);

        $competition_score = $this->calculate_competition_score(
            $total_products,
            $avg_reviews,
            $avg_rating,
            $price_std,
            $review_std
        );

        return array(
            'level' => $this->get_competition_level($competition_score),
            'score' => $competition_score,
            'stats' => array(
                'total_products' => $total_products,
                'avg_price' => $avg_price,
                'avg_reviews' => $avg_reviews,
                'avg_rating' => $avg_rating,
                'price_deviation' => $price_std,
                'review_deviation' => $review_std
            )
        );
    }

    /**
     * 리뷰 감성 분석
     *
     * @param array $reviews 리뷰 목록
     * @return array 감성 분석 결과
     */
    private function analyze_review_sentiment($reviews) {
        $positive = 0;
        $negative = 0;
        $neutral = 0;

        foreach ($reviews as $review) {
            $sentiment = $this->classify_sentiment($review['content']);
            switch ($sentiment) {
                case 'positive':
                    $positive++;
                    break;
                case 'negative':
                    $negative++;
                    break;
                default:
                    $neutral++;
            }
        }

        $total = count($reviews);
        return array(
            'positive' => array(
                'count' => $positive,
                'percentage' => $total > 0 ? ($positive / $total) * 100 : 0
            ),
            'negative' => array(
                'count' => $negative,
                'percentage' => $total > 0 ? ($negative / $total) * 100 : 0
            ),
            'neutral' => array(
                'count' => $neutral,
                'percentage' => $total > 0 ? ($neutral / $total) * 100 : 0
            )
        );
    }

    /**
     * HTTP 요청 실행
     *
     * @param string $url 요청 URL
     * @return string|false 응답 데이터
     */
    private function make_request($url) {
        // 요청 간격 제한
        $current_time = time();
        $time_since_last_request = $current_time - $this->last_request_time;
        
        if ($time_since_last_request < $this->request_interval) {
            sleep($this->request_interval - $time_since_last_request);
        }

        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'headers' => array(
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            )
        ));

        $this->last_request_time = time();

        if (is_wp_error($response)) {
            $this->log_error('HTTP 요청 오류: ' . $response->get_error_message());
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            $this->log_error('HTTP 응답 오류: ' . $response_code);
            return false;
        }

        return wp_remote_retrieve_body($response);
    }

    /**
     * 캐시 데이터 가져오기
     *
     * @param string $key 캐시 키
     * @return mixed 캐시 데이터
     */
    private function get_cache($key) {
        $cache = wp_cache_get('rocket_sourcer_crawler_cache');
        return isset($cache[$key]) ? $cache[$key] : false;
    }

    /**
     * 캐시 데이터 저장
     *
     * @param string $key 캐시 키
     * @param mixed $data 저장할 데이터
     */
    private function set_cache($key, $data) {
        $cache = wp_cache_get('rocket_sourcer_crawler_cache');
        $cache[$key] = $data;
        wp_cache_set('rocket_sourcer_crawler_cache', $cache, '', $this->cache_expiration);
    }

    /**
     * 오류 로깅
     *
     * @param string $message 오류 메시지
     */
    private function log_error($message) {
        error_log('[Rocket Sourcer Crawler] ' . $message);
    }

    /**
     * 표준 편차 계산
     *
     * @param array $numbers 숫자 배열
     * @return float 표준 편차
     */
    private function calculate_std_deviation($numbers) {
        $count = count($numbers);
        if ($count === 0) {
            return 0;
        }

        $mean = array_sum($numbers) / $count;
        $variance = 0;

        foreach ($numbers as $number) {
            $variance += pow($number - $mean, 2);
        }

        return sqrt($variance / $count);
    }

    /**
     * 경쟁 강도 점수 계산
     *
     * @param int $total_products 총 제품 수
     * @param float $avg_reviews 평균 리뷰 수
     * @param float $avg_rating 평균 평점
     * @param float $price_std 가격 표준 편차
     * @param float $review_std 리뷰 표준 편차
     * @return float 경쟁 강도 점수
     */
    private function calculate_competition_score($total_products, $avg_reviews, $avg_rating, $price_std, $review_std) {
        // 각 요소별 가중치
        $weights = array(
            'products' => 0.3,
            'reviews' => 0.25,
            'rating' => 0.2,
            'price_std' => 0.15,
            'review_std' => 0.1
        );

        // 각 요소 정규화
        $normalized_products = min($total_products / 100, 1);
        $normalized_reviews = min($avg_reviews / 1000, 1);
        $normalized_rating = $avg_rating / 5;
        $normalized_price_std = min($price_std / 100000, 1);
        $normalized_review_std = min($review_std / 1000, 1);

        // 가중 평균 계산
        return (
            $weights['products'] * $normalized_products +
            $weights['reviews'] * $normalized_reviews +
            $weights['rating'] * $normalized_rating +
            $weights['price_std'] * $normalized_price_std +
            $weights['review_std'] * $normalized_review_std
        ) * 100;
    }

    /**
     * 경쟁 강도 레벨 결정
     *
     * @param float $score 경쟁 강도 점수
     * @return string 경쟁 강도 레벨
     */
    private function get_competition_level($score) {
        if ($score < 30) {
            return 'low';
        } elseif ($score < 70) {
            return 'medium';
        } else {
            return 'high';
        }
    }
} 