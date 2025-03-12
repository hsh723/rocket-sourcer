<?php
/**
 * 프론트엔드 기능 클래스
 *
 * @package RocketSourcer
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 프론트엔드 기능을 처리하는 클래스
 */
class Rocket_Sourcer_Public {

    /**
     * 프론트엔드 스타일 로드
     */
    public function enqueue_styles() {
        wp_enqueue_style('rocket-sourcer-public', ROCKET_SOURCER_URL . 'assets/css/public.css', array(), ROCKET_SOURCER_VERSION, 'all');
    }

    /**
     * 프론트엔드 스크립트 로드
     */
    public function enqueue_scripts() {
        wp_enqueue_script('rocket-sourcer-public', ROCKET_SOURCER_URL . 'assets/js/public.js', array('jquery'), ROCKET_SOURCER_VERSION, false);
    }

    /**
     * [rocket_sourcer] 단축코드 처리
     *
     * @param array $atts 단축코드 속성
     * @return string 출력 HTML
     */
    public function display_sourcer($atts) {
        $atts = shortcode_atts(array(
            'type' => 'dashboard',
        ), $atts, 'rocket_sourcer');
        
        ob_start();
        
        if ($atts['type'] === 'dashboard') {
            require_once ROCKET_SOURCER_PATH . 'public/partials/rocket-sourcer-public-dashboard.php';
        } elseif ($atts['type'] === 'calculator') {
            require_once ROCKET_SOURCER_PATH . 'public/partials/rocket-sourcer-public-calculator.php';
        }
        
        return ob_get_clean();
    }
} 