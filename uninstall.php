<?php
/**
 * Rocket Sourcer 제거 스크립트
 *
 * @package    Rocket_Sourcer
 */

// WordPress가 아닌 곳에서 직접 접근 방지
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// 옵션 삭제
$options = array(
    'rocket_sourcer_api_key',
    'rocket_sourcer_api_secret',
    'rocket_sourcer_result_lifetime',
    'rocket_sourcer_settings'
);

foreach ($options as $option) {
    delete_option($option);
}

// 데이터베이스 테이블 삭제
global $wpdb;
$tables = array(
    $wpdb->prefix . 'rocket_sourcer_keywords',
    $wpdb->prefix . 'rocket_sourcer_products',
    $wpdb->prefix . 'rocket_sourcer_margins',
    $wpdb->prefix . 'rocket_sourcer_searches'
);

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS $table");
}

// 캐시 정리
wp_cache_flush(); 