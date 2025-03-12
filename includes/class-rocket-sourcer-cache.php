<?php
/**
 * 데이터 캐싱 처리 클래스
 *
 * @package    Rocket_Sourcer
 * @subpackage Rocket_Sourcer/includes
 */

class Rocket_Sourcer_Cache {

    /**
     * 캐시 그룹
     */
    private $group = 'rocket_sourcer';

    /**
     * 캐시 만료 시간 (초)
     */
    private $expiration;

    /**
     * 생성자
     */
    public function __construct() {
        $this->expiration = get_option('rocket_sourcer_result_lifetime', 30) * DAY_IN_SECONDS;
    }

    /**
     * 캐시 키 생성
     *
     * @param string $type 데이터 유형
     * @param string $identifier 식별자
     * @return string 캐시 키
     */
    private function generate_key($type, $identifier) {
        return md5($type . '_' . $identifier);
    }

    /**
     * 데이터 캐시 저장
     *
     * @param string $type 데이터 유형
     * @param string $identifier 식별자
     * @param mixed $data 저장할 데이터
     * @param int $expiration 만료 시간 (초)
     * @return bool 저장 성공 여부
     */
    public function set($type, $identifier, $data, $expiration = null) {
        if ($expiration === null) {
            $expiration = $this->expiration;
        }

        $key = $this->generate_key($type, $identifier);
        return wp_cache_set($key, $data, $this->group, $expiration);
    }

    /**
     * 캐시된 데이터 가져오기
     *
     * @param string $type 데이터 유형
     * @param string $identifier 식별자
     * @return mixed|false 캐시된 데이터 또는 false
     */
    public function get($type, $identifier) {
        $key = $this->generate_key($type, $identifier);
        return wp_cache_get($key, $this->group);
    }

    /**
     * 캐시 삭제
     *
     * @param string $type 데이터 유형
     * @param string $identifier 식별자
     * @return bool 삭제 성공 여부
     */
    public function delete($type, $identifier) {
        $key = $this->generate_key($type, $identifier);
        return wp_cache_delete($key, $this->group);
    }

    /**
     * 캐시 그룹 전체 삭제
     *
     * @return bool 삭제 성공 여부
     */
    public function flush() {
        return wp_cache_flush();
    }

    /**
     * 키워드 분석 결과 캐시 저장
     *
     * @param string $keyword 키워드
     * @param array $data 분석 결과 데이터
     * @return bool 저장 성공 여부
     */
    public function set_keyword_analysis($keyword, $data) {
        return $this->set('keyword_analysis', $keyword, $data);
    }

    /**
     * 캐시된 키워드 분석 결과 가져오기
     *
     * @param string $keyword 키워드
     * @return array|false 분석 결과 데이터 또는 false
     */
    public function get_keyword_analysis($keyword) {
        return $this->get('keyword_analysis', $keyword);
    }

    /**
     * 제품 분석 결과 캐시 저장
     *
     * @param string $product_id 제품 ID
     * @param array $data 분석 결과 데이터
     * @return bool 저장 성공 여부
     */
    public function set_product_analysis($product_id, $data) {
        return $this->set('product_analysis', $product_id, $data);
    }

    /**
     * 캐시된 제품 분석 결과 가져오기
     *
     * @param string $product_id 제품 ID
     * @return array|false 분석 결과 데이터 또는 false
     */
    public function get_product_analysis($product_id) {
        return $this->get('product_analysis', $product_id);
    }

    /**
     * 제품 검색 결과 캐시 저장
     *
     * @param string $keyword 검색어
     * @param array $data 검색 결과 데이터
     * @return bool 저장 성공 여부
     */
    public function set_product_search($keyword, $data) {
        // 검색 결과는 짧은 시간 동안만 캐시
        return $this->set('product_search', $keyword, $data, HOUR_IN_SECONDS);
    }

    /**
     * 캐시된 제품 검색 결과 가져오기
     *
     * @param string $keyword 검색어
     * @return array|false 검색 결과 데이터 또는 false
     */
    public function get_product_search($keyword) {
        return $this->get('product_search', $keyword);
    }

    /**
     * 인기 키워드 캐시 저장
     *
     * @param string $category 카테고리
     * @param array $data 키워드 데이터
     * @return bool 저장 성공 여부
     */
    public function set_popular_keywords($category, $data) {
        // 인기 키워드는 하루 동안 캐시
        return $this->set('popular_keywords', $category, $data, DAY_IN_SECONDS);
    }

    /**
     * 캐시된 인기 키워드 가져오기
     *
     * @param string $category 카테고리
     * @return array|false 키워드 데이터 또는 false
     */
    public function get_popular_keywords($category) {
        return $this->get('popular_keywords', $category);
    }

    /**
     * 마진 계산 결과 캐시 저장
     *
     * @param string $identifier 식별자
     * @param array $data 계산 결과 데이터
     * @return bool 저장 성공 여부
     */
    public function set_margin_calculation($identifier, $data) {
        return $this->set('margin_calculation', $identifier, $data);
    }

    /**
     * 캐시된 마진 계산 결과 가져오기
     *
     * @param string $identifier 식별자
     * @return array|false 계산 결과 데이터 또는 false
     */
    public function get_margin_calculation($identifier) {
        return $this->get('margin_calculation', $identifier);
    }

    /**
     * 오래된 캐시 데이터 정리
     *
     * @return void
     */
    public function cleanup() {
        // WordPress의 object cache는 자동으로 만료된 캐시를 정리하므로
        // 추가적인 정리 작업이 필요하지 않습니다.
    }
} 