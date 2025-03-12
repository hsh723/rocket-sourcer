<?php
/**
 * Rocket Sourcer
 *
 * @package           RocketSourcer
 * @author            AI Developer
 * @copyright         2025 Your Company
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Rocket Sourcer
 * Plugin URI:        https://example.com/rocket-sourcer
 * Description:       쿠팡 로켓그로스 셀러를 위한 소싱 추천 및 분석 도구
 * Version:           1.0.0
 * Author:            AI Developer
 * Author URI:        https://example.com
 * Text Domain:       rocket-sourcer
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}

// 플러그인 경로 및 URL 상수 정의
define('ROCKET_SOURCER_PATH', plugin_dir_path(__FILE__));
define('ROCKET_SOURCER_URL', plugin_dir_url(__FILE__));
define('ROCKET_SOURCER_VERSION', '1.0.0');

// 클래스 파일 로드
require_once ROCKET_SOURCER_PATH . 'includes/class-rocket-sourcer.php';

// 플러그인 활성화 및 비활성화 훅
register_activation_hook(__FILE__, array('Rocket_Sourcer', 'activate'));
register_deactivation_hook(__FILE__, array('Rocket_Sourcer', 'deactivate'));

// 플러그인 실행
function run_rocket_sourcer() {
    $plugin = new Rocket_Sourcer();
    $plugin->run();
}
run_rocket_sourcer(); 