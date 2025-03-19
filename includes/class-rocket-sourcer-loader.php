<?php
/**
 * 플러그인의 액션과 필터를 등록하고 관리하는 클래스
 *
 * @package    Rocket_Sourcer
 * @subpackage Rocket_Sourcer/includes
 */
class Rocket_Sourcer_Loader {

    /**
     * 등록된 액션들의 배열
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $actions    등록된 액션들을 저장하는 배열
     */
    protected $actions;

    /**
     * 등록된 필터들의 배열
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $filters    등록된 필터들을 저장하는 배열
     */
    protected $filters;

    /**
     * 초기화
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->actions = array();
        $this->filters = array();
    }

    /**
     * 워드프레스 액션 훅에 새로운 콜백 함수를 추가
     *
     * @since    1.0.0
     * @param    string    $hook             액션이 추가될 훅의 이름
     * @param    object    $component        액션을 소유한 객체의 참조
     * @param    string    $callback         객체에서 실행될 메서드의 이름
     * @param    int       $priority         액션이 실행될 우선순위
     * @param    int       $accepted_args    콜백이 받아들일 인자의 수
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * 워드프레스 필터 훅에 새로운 콜백 함수를 추가
     *
     * @since    1.0.0
     * @param    string    $hook             필터가 추가될 훅의 이름
     * @param    object    $component        필터를 소유한 객체의 참조
     * @param    string    $callback         객체에서 실행될 메서드의 이름
     * @param    int       $priority         필터가 실행될 우선순위
     * @param    int       $accepted_args    콜백이 받아들일 인자의 수
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * 컬렉션에 새로운 훅을 추가하는 유틸리티 함수
     *
     * @since    1.0.0
     * @access   private
     * @param    array     $hooks            등록된 훅들이 저장된 배열
     * @param    string    $hook             훅의 이름
     * @param    object    $component        훅을 소유한 객체의 참조
     * @param    string    $callback         객체에서 실행될 메서드의 이름
     * @param    int       $priority         훅이 실행될 우선순위
     * @param    int       $accepted_args    콜백이 받아들일 인자의 수
     * @return   array                       훅이 추가된 컬렉션
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
     * 워드프레스에 등록된 모든 필터와 액션을 실행
     *
     * @since    1.0.0
     */
    public function run() {
        foreach ($this->filters as $hook) {
            add_filter(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }

        foreach ($this->actions as $hook) {
            add_action(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }
    }
} 