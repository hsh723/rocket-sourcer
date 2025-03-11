<?php
/**
 * 플러그인의 모든 액션과 필터를 등록하고 유지하는 클래스
 *
 * @since      1.0.0
 * @package    Rocket_Sourcer
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 플러그인의 모든 액션과 필터를 등록하고 유지하는 클래스
 *
 * 이 클래스는 플러그인의 모든 액션과 필터를 유지하고 등록합니다.
 * 또한 단축코드를 등록하는 기능도 포함합니다.
 *
 * @since      1.0.0
 * @package    Rocket_Sourcer
 * @author     AI Developer
 */
class Rocket_Sourcer_Loader {

    /**
     * 플러그인에 등록된 모든 액션의 배열
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $actions    플러그인에 등록된 모든 액션
     */
    protected $actions;

    /**
     * 플러그인에 등록된 모든 필터의 배열
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $filters    플러그인에 등록된 모든 필터
     */
    protected $filters;

    /**
     * 플러그인에 등록된 모든 단축코드의 배열
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $shortcodes    플러그인에 등록된 모든 단축코드
     */
    protected $shortcodes;

    /**
     * 클래스 생성자
     *
     * 액션, 필터, 단축코드 배열을 초기화합니다.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->actions = array();
        $this->filters = array();
        $this->shortcodes = array();
    }

    /**
     * 새 액션을 액션 배열에 추가합니다.
     *
     * @since    1.0.0
     * @param    string    $hook             액션이 연결될 WordPress 액션 이름
     * @param    object    $component        액션이 호출될 객체의 참조
     * @param    string    $callback         객체에서 호출될 메서드의 이름
     * @param    int       $priority         액션이 실행될 우선순위 (기본값: 10)
     * @param    int       $accepted_args    콜백 함수가 받아들이는 인수의 수 (기본값: 1)
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * 새 필터를 필터 배열에 추가합니다.
     *
     * @since    1.0.0
     * @param    string    $hook             필터가 연결될 WordPress 필터 이름
     * @param    object    $component        필터가 호출될 객체의 참조
     * @param    string    $callback         객체에서 호출될 메서드의 이름
     * @param    int       $priority         필터가 실행될 우선순위 (기본값: 10)
     * @param    int       $accepted_args    콜백 함수가 받아들이는 인수의 수 (기본값: 1)
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * 새 단축코드를 단축코드 배열에 추가합니다.
     *
     * @since    1.0.0
     * @param    string    $tag              단축코드 태그
     * @param    object    $component        단축코드가 호출될 객체의 참조
     * @param    string    $callback         객체에서 호출될 메서드의 이름
     */
    public function add_shortcode($tag, $component, $callback) {
        $this->shortcodes = $this->add_shortcode_internal($this->shortcodes, $tag, $component, $callback);
    }

    /**
     * 지정된 배열에 후크를 추가하는 유틸리티 함수
     *
     * @since    1.0.0
     * @access   private
     * @param    array     $hooks            후크가 등록될 배열
     * @param    string    $hook             후크의 이름
     * @param    object    $component        후크가 호출될 객체의 참조
     * @param    string    $callback         객체에서 호출될 메서드의 이름
     * @param    int       $priority         후크가 실행될 우선순위
     * @param    int       $accepted_args    콜백 함수가 받아들이는 인수의 수
     * @return   array                       후크가 등록된 배열
     */
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );

        return $hooks;
    }

    /**
     * 지정된 배열에 단축코드를 추가하는 유틸리티 함수
     *
     * @since    1.0.0
     * @access   private
     * @param    array     $shortcodes       단축코드가 등록될 배열
     * @param    string    $tag              단축코드 태그
     * @param    object    $component        단축코드가 호출될 객체의 참조
     * @param    string    $callback         객체에서 호출될 메서드의 이름
     * @return   array                       단축코드가 등록된 배열
     */
    private function add_shortcode_internal($shortcodes, $tag, $component, $callback) {
        $shortcodes[] = array(
            'tag'           => $tag,
            'component'     => $component,
            'callback'      => $callback
        );

        return $shortcodes;
    }

    /**
     * 플러그인에 등록된 모든 액션과 필터를 WordPress에 등록합니다.
     *
     * @since    1.0.0
     */
    public function run() {
        foreach ($this->filters as $hook) {
            add_filter($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }

        foreach ($this->actions as $hook) {
            add_action($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }

        foreach ($this->shortcodes as $shortcode) {
            add_shortcode($shortcode['tag'], array($shortcode['component'], $shortcode['callback']));
        }
    }
} 