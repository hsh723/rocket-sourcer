<?php
/**
 * 키워드 분석 클래스
 *
 * @package    Rocket_Sourcer
 * @subpackage Rocket_Sourcer/includes
 */

class Rocket_Sourcer_Keywords {

    /**
     * 카테고리별 인기 키워드 가져오기
     *
     * @param string $category 카테고리명
     * @param int    $limit    가져올 키워드 수
     * @return array 인기 키워드 목록
     */
    public function get_popular_keywords($category, $limit = 10) {
        // 샘플 데이터 - 실제로는 쿠팡 API를 통해 데이터를 가져와야 함
        $sample_keywords = array(
            'fashion' => array(
                array('keyword' => '여성원피스', 'volume' => 15000, 'competition' => 'high'),
                array('keyword' => '남자반팔티', 'volume' => 12000, 'competition' => 'medium'),
                array('keyword' => '여름샌들', 'volume' => 9000, 'competition' => 'low'),
                array('keyword' => '청바지', 'volume' => 8500, 'competition' => 'high'),
                array('keyword' => '운동화', 'volume' => 7800, 'competition' => 'high')
            ),
            'beauty' => array(
                array('keyword' => '선크림', 'volume' => 25000, 'competition' => 'high'),
                array('keyword' => '립스틱', 'volume' => 18000, 'competition' => 'medium'),
                array('keyword' => '마스카라', 'volume' => 12000, 'competition' => 'medium'),
                array('keyword' => '스킨로션', 'volume' => 11000, 'competition' => 'high'),
                array('keyword' => '클렌징폼', 'volume' => 9500, 'competition' => 'low')
            )
        );

        return isset($sample_keywords[$category]) 
            ? array_slice($sample_keywords[$category], 0, $limit) 
            : array();
    }

    /**
     * 키워드 분석하기
     *
     * @param string $keyword 분석할 키워드
     * @return array 키워드 분석 결과
     */
    public function analyze_keyword($keyword) {
        // 샘플 데이터 - 실제로는 쿠팡 API와 검색 데이터를 분석해야 함
        $monthly_data = array(
            'Jan' => rand(8000, 12000),
            'Feb' => rand(8000, 12000),
            'Mar' => rand(8000, 12000),
            'Apr' => rand(8000, 12000),
            'May' => rand(8000, 12000),
            'Jun' => rand(8000, 12000)
        );

        $total_volume = array_sum($monthly_data);
        $avg_volume = $total_volume / count($monthly_data);
        $last_month = end($monthly_data);
        $trend = $last_month > $avg_volume ? 'up' : ($last_month < $avg_volume ? 'down' : 'stable');

        return array(
            'keyword' => $keyword,
            'volume' => array(
                'total' => $total_volume,
                'average' => $avg_volume,
                'monthly' => $monthly_data
            ),
            'competition' => array(
                'level' => $this->calculate_competition_level($total_volume),
                'score' => rand(1, 100),
                'difficulty' => rand(1, 5)
            ),
            'trend' => array(
                'direction' => $trend,
                'percentage' => rand(-20, 20)
            ),
            'related_keywords' => $this->get_related_keywords($keyword),
            'estimated_cpc' => rand(500, 2000)
        );
    }

    /**
     * 경쟁 강도 계산
     *
     * @param int $volume 검색량
     * @return string 경쟁 강도 (low/medium/high)
     */
    private function calculate_competition_level($volume) {
        if ($volume > 20000) {
            return 'high';
        } elseif ($volume > 10000) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * 연관 키워드 가져오기
     *
     * @param string $keyword 기준 키워드
     * @return array 연관 키워드 목록
     */
    private function get_related_keywords($keyword) {
        // 샘플 데이터
        return array(
            array('keyword' => $keyword . ' 추천', 'volume' => rand(1000, 5000)),
            array('keyword' => $keyword . ' 가격', 'volume' => rand(1000, 5000)),
            array('keyword' => $keyword . ' 리뷰', 'volume' => rand(1000, 5000)),
            array('keyword' => '인기 ' . $keyword, 'volume' => rand(1000, 5000)),
            array('keyword' => '최저가 ' . $keyword, 'volume' => rand(1000, 5000))
        );
    }
} 