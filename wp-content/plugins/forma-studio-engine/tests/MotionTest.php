<?php

namespace Forma\Engine\Tests;

defined( 'ABSPATH' ) || exit;

final class MotionTest extends TestCase {

	public function test_controls_exist_on_widgets_and_containers(): void {
		$heading   = \Elementor\Plugin::$instance->widgets_manager->get_widget_types( 'heading' )->get_controls();
		$container = \Elementor\Plugin::$instance->elements_manager->get_element_types( 'container' )->get_controls();

		foreach ( array( 'fm_entrance', 'fm_scroll', 'fm_speed', 'fm_delay', 'fm_stagger', 'fm_cursor', 'fm_shared' ) as $control ) {
			$this->assert_true( isset( $heading[ $control ] ), "heading has {$control}" );
			$this->assert_true( isset( $container[ $control ] ), "container has {$control}" );
		}
	}

	public function test_controls_are_declared_once_per_stack(): void {
		$errors = array();
		$spy    = static function ( string $function_name, string $message ) use ( &$errors ): void {
			$errors[] = $message;
		};
		add_action( 'doing_it_wrong_run', $spy, 10, 2 );

		$manager = \Elementor\Plugin::$instance->controls_manager;

		foreach ( array( 'heading', 'forma-marquee' ) as $name ) {
			$widget = \Elementor\Plugin::$instance->widgets_manager->get_widget_types( $name );
			$manager->delete_stack( $widget );
			$widget->get_controls();
		}

		remove_action( 'doing_it_wrong_run', $spy, 10 );

		$this->assert_same( array(), array_values( array_filter( $errors, static fn( $m ) => str_contains( $m, 'fm_' ) || str_contains( $m, 'forma_motion' ) ) ), 'no duplicate Forma Motion controls' );
	}

	public function test_render_adds_only_set_attributes_and_enqueues_motion(): void {
		$html = $this->render_heading(
			array(
				'fm_entrance' => 'lines',
				'fm_delay'    => 0.2,
				'fm_cursor'   => 'View <b>',
			)
		);

		$this->assert_true( str_contains( $html, 'data-fm-entrance="lines"' ), 'entrance attribute' );
		$this->assert_true( str_contains( $html, 'data-fm-delay="0.2"' ), 'delay attribute' );
		$this->assert_true( str_contains( $html, 'data-cursor="View"' ), 'cursor label sanitised' );
		$this->assert_true( ! str_contains( $html, 'data-fm-scroll' ), 'unset scroll omitted' );
		$this->assert_true( wp_script_is( 'forma-motion', 'enqueued' ), 'motion script enqueued' );
	}

	public function test_plain_widgets_do_not_load_motion(): void {
		wp_dequeue_script( 'forma-motion' );
		$html = $this->render_heading( array() );

		$this->assert_true( ! str_contains( $html, 'data-fm-' ), 'no attributes' );
		$this->assert_true( ! wp_script_is( 'forma-motion', 'enqueued' ), 'motion not enqueued' );
	}

	public function test_animated_elements_bypass_the_element_cache(): void {
		$widget = \Elementor\Plugin::$instance->widgets_manager->get_widget_types( 'heading' );

		$this->assert_true( apply_filters( 'elementor/element/is_dynamic_content', false, array( 'settings' => array( 'fm_entrance' => 'lines' ) ), $widget ), 'animated → dynamic' );
		$this->assert_true( ! apply_filters( 'elementor/element/is_dynamic_content', false, array( 'settings' => array( 'title' => 'x' ) ), $widget ), 'plain → cacheable' );
	}

	public function test_shared_transition_names_the_current_project(): void {
		$id              = (int) wp_insert_post(
			array(
				'post_type'   => 'forma_project',
				'post_title'  => 'Shared',
				'post_status' => 'publish',
			)
		);
		$GLOBALS['post'] = get_post( $id );
		setup_postdata( $GLOBALS['post'] );

		$html = $this->render_heading( array( 'fm_shared' => 'yes' ) );
		wp_reset_postdata();

		$this->assert_true( str_contains( $html, "view-transition-name: forma-project-{$id}" ), 'view-transition-name set' );
	}

	public function test_head_snippet_marks_js_and_has_a_failsafe(): void {
		ob_start();
		do_action( 'wp_head' );
		$head = (string) ob_get_clean();

		$this->assert_true( str_contains( $head, "classList.add('fm-js')" ), 'fm-js class script' );
		$this->assert_true( str_contains( $head, 'fm-failsafe' ), 'failsafe reveal' );
		$this->assert_true( str_contains( $head, 'prefers-reduced-motion: no-preference' ), 'hidden only when motion is allowed' );
	}

	private function render_heading( array $settings ): string {
		$widget = \Elementor\Plugin::$instance->elements_manager->create_element_instance(
			array(
				'id'         => 'fmtest1',
				'elType'     => 'widget',
				'widgetType' => 'heading',
				'settings'   => array( 'title' => 'Motion test' ) + $settings,
			)
		);

		ob_start();
		$widget->print_element();
		return (string) ob_get_clean();
	}
}
