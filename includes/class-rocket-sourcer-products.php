<?php
/**
 * 제품 분석 클래스
 *
 * @package    Rocket_Sourcer
 * @subpackage Rocket_Sourcer/includes
 */

class Rocket_Sourcer_Products {

    /**
     * 키워드로 제품 검색하기
     *
     * @param string $keyword 검색 키워드
     * @param array  $options 검색 옵션 (정렬, 필터 등)
     * @return array 검색된 제품 목록
     */
    public function search_products($keyword, $options = array()) {
        // 샘플 데이터 - 실제로는 쿠팡 API를 통해 검색해야 함
        $sample_products = array(
            array(
                'id' => 'P' . rand(10000, 99999),
                'title' => '프리미엄 ' . $keyword,
                'price' => rand(10000, 50000),
                'original_price' => rand(15000, 60000),
                'rating' => rand(40, 50) / 10,
                'review_count' => rand(100, 1000),
                'seller' => '베스트샵',
                'free_shipping' => true,
                'rocket_delivery' => true,
                'image_url' => 'https://example.com/image1.jpg'
            ),
            array(
                'id' => 'P' . rand(10000, 99999),
                'title' => '고급형 ' . $keyword,
                'price' => rand(8000, 40000),
                'original_price' => rand(12000, 50000),
                'rating' => rand(40, 50) / 10,
                'review_count' => rand(50, 500),
                'seller' => '스마트스토어',
                'free_shipping' => true,
                'rocket_delivery' => false,
                'image_url' => 'https://example.com/image2.jpg'
            ),
            array(
                'id' => 'P' . rand(10000, 99999),
                'title' => '실속형 ' . $keyword,
                'price' => rand(5000, 30000),
                'original_price' => rand(8000, 40000),
                'rating' => rand(40, 50) / 10,
                'review_count' => rand(10, 200),
                'seller' => '마켓플레이스',
                'free_shipping' => false,
                'rocket_delivery' => false,
                'image_url' => 'https://example.com/image3.jpg'
            )
        );

        // 정렬 옵션 처리
        if (isset($options['sort'])) {
            usort($sample_products, function($a, $b) use ($options) {
                switch ($options['sort']) {
                    case 'price_asc':
                        return $a['price'] - $b['price'];
                    case 'price_desc':
                        return $b['price'] - $a['price'];
                    case 'rating':
                        return $b['rating'] - $a['rating'];
                    case 'review_count':
                        return $b['review_count'] - $a['review_count'];
                    default:
                        return 0;
                }
            });
        }

        return $sample_products;
    }

    /**
     * 제품 상세 정보 분석하기
     *
     * @param string $product_id 제품 ID
     * @return array 제품 분석 결과
     */
    public function analyze_product($product_id) {
        // 샘플 데이터 - 실제로는 쿠팡 API를 통해 상세 정보를 가져와야 함
        return array(
            'basic_info' => array(
                'id' => $product_id,
                'title' => '샘플 제품명',
                'category' => '패션의류',
                'price' => rand(10000, 50000),
                'original_price' => rand(15000, 60000),
                'discount_rate' => rand(5, 30),
                'rating' => rand(40, 50) / 10,
                'review_count' => rand(100, 1000),
                'qa_count' => rand(10, 50),
                'seller' => array(
                    'name' => '베스트샵',
                    'rating' => rand(40, 50) / 10,
                    'product_count' => rand(100, 1000),
                    'follower_count' => rand(1000, 5000)
                )
            ),
            'sales_metrics' => array(
                'daily_sales' => rand(10, 100),
                'weekly_sales' => rand(50, 500),
                'monthly_sales' => rand(200, 2000),
                'sales_rank' => rand(1, 100),
                'category_rank' => rand(1, 50)
            ),
            'price_history' => array(
                'lowest' => rand(8000, 40000),
                'highest' => rand(15000, 60000),
                'average' => rand(10000, 50000),
                'price_changes' => array(
                    array('date' => '2024-01-01', 'price' => rand(10000, 50000)),
                    array('date' => '2024-02-01', 'price' => rand(10000, 50000)),
                    array('date' => '2024-03-01', 'price' => rand(10000, 50000))
                )
            ),
            'review_analysis' => array(
                'rating_distribution' => array(
                    5 => rand(50, 200),
                    4 => rand(30, 150),
                    3 => rand(10, 50),
                    2 => rand(5, 20),
                    1 => rand(1, 10)
                ),
                'positive_keywords' => array(
                    '품질' => rand(10, 50),
                    '배송' => rand(10, 50),
                    '가격' => rand(10, 50)
                ),
                'negative_keywords' => array(
                    '사이즈' => rand(5, 20),
                    '색상' => rand(5, 20),
                    '재질' => rand(5, 20)
                )
            ),
            'market_position' => array(
                'price_competitiveness' => rand(1, 100),
                'market_share' => rand(1, 10),
                'growth_potential' => rand(1, 100),
                'competition_level' => array('high', 'medium', 'low')[rand(0, 2)]
            )
        );
    }

    /**
     * 제품의 최적화 점수 계산
     *
     * @param array $product_data 제품 데이터
     * @return array 최적화 점수
     */
    public function calculate_optimization_scores($product_data) {
        return array(
            'title' => rand(60, 100),      // 제목 최적화 점수
            'image' => rand(60, 100),      // 이미지 품질 점수
            'description' => rand(60, 100), // 설명 충실도 점수
            'overall' => rand(60, 100)      // 전체 최적화 점수
        );
    }
} 