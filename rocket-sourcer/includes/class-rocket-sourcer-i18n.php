<?php
/**
 * 플러그인의 국제화를 정의하는 클래스
 *
 * @since      1.0.0
 * @package    Rocket_Sourcer
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 플러그인의 국제화를 정의하는 클래스
 *
 * 이 클래스는 플러그인의 텍스트 도메인을 로드합니다.
 *
 * @since      1.0.0
 * @package    Rocket_Sourcer
 * @author     AI Developer
 */
class Rocket_Sourcer_i18n {

    /**
     * 플러그인의 텍스트 도메인을 로드합니다.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'rocket-sourcer',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
} 