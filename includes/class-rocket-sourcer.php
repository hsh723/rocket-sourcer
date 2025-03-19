<?php
/**
 * 메인 플러그인 클래스
 *
 * @package    Rocket_Sourcer
 * @subpackage Rocket_Sourcer/includes
 */

class Rocket_Sourcer {

    /**
     * 플러그인 로더
     *
     * @since    1.0.0
     * @access   protected
     * @var      Rocket_Sourcer_Loader    $loader    플러그인의 훅과 필터를 관리
     */
    protected $loader;

    /**
     * 플러그인의 고유 식별자
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    플러그인의 고유 식별자
     */
    protected $plugin_name;

    /**
     * 플러그인의 현재 버전
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    플러그인의 현재 버전
     */
    protected $version;

    /**
     * 클래스 초기화
     *
     * @since    1.0.0
     */
    public function __construct() {
        if (defined('ROCKET_SOURCER_VERSION')) {
            $this->version = ROCKET_SOURCER_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'rocket-sourcer';

        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * 플러그인 실행에 필요한 의존성 로드
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        // 로더 클래스
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-rocket-sourcer-loader.php';
        
        // 관리자 클래스
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-rocket-sourcer-admin.php';

        // 프론트엔드 클래스
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-rocket-sourcer-public.php';

        // API 클라이언트
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-rocket-sourcer-coupang-client.php';

        // 캐시 클래스
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-rocket-sourcer-cache.php';

        // DB 클래스
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-rocket-sourcer-db.php';

        $this->loader = new Rocket_Sourcer_Loader();
    }

    /**
     * 관리자 영역 훅 정의
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new Rocket_Sourcer_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
    }

    /**
     * 프론트엔드 훅 정의
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new Rocket_Sourcer_Public();
        
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        
        // 단축코드 등록
        add_shortcode('rocket_sourcer', array($plugin_public, 'display_sourcer'));
    }

    /**
     * 플러그인 실행
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * 플러그인의 이름을 반환
     *
     * @since     1.0.0
     * @return    string    플러그인 이름
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * 로더 객체 반환
     *
     * @since     1.0.0
     * @return    Rocket_Sourcer_Loader    플러그인의 훅과 필터를 관리하는 로더
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * 플러그인의 버전 번호를 반환
     *
     * @since     1.0.0
     * @return    string    플러그인의 버전 번호
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * 플러그인 활성화 시 실행되는 메서드
     */
    public static function activate() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-rocket-sourcer-activator.php';
        Rocket_Sourcer_Activator::activate();
    }

    /**
     * 플러그인 비활성화 시 실행되는 메서드
     */
    public static function deactivate() {
        // 필요한 정리 작업 수행
    }
} 