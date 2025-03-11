<?php
/**
 * 플러그인 비활성화 시 실행되는 기능을 정의하는 클래스
 *
 * @since      1.0.0
 * @package    Rocket_Sourcer
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 플러그인 비활성화 시 실행되는 기능을 정의하는 클래스
 *
 * 이 클래스는 플러그인이 비활성화될 때 필요한 정리 작업을 수행합니다.
 *
 * @since      1.0.0
 * @package    Rocket_Sourcer
 * @author     AI Developer
 */
class Rocket_Sourcer_Deactivator {

    /**
     * 플러그인 비활성화 시 실행되는 메서드
     *
     * 이 메서드는 플러그인이 비활성화될 때 필요한 정리 작업을 수행합니다.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // 예약된 이벤트 제거
        wp_clear_scheduled_hook('rocket_sourcer_daily_update');
        
        // 리라이트 규칙 플러시
        flush_rewrite_rules();
    }
} 