<?php

namespace Forma\Engine\Tests;

defined( 'ABSPATH' ) || exit;

final class FrontEndTest extends TestCase {

	public function test_cursor_loads_on_the_front_end(): void {
		do_action( 'wp_enqueue_scripts' );

		$this->assert_true( wp_script_is( 'forma-cursor', 'enqueued' ), 'cursor script enqueued' );
		$this->assert_true( wp_style_is( 'forma-cursor', 'enqueued' ), 'cursor style enqueued' );
		$this->assert_true( ! wp_script_is( 'forma-motion', 'enqueued' ), 'motion still waits for an animated element' );
	}

	public function test_page_transitions_are_declared_in_the_head(): void {
		ob_start();
		do_action( 'wp_head' );
		$head = (string) ob_get_clean();

		$this->assert_true( str_contains( $head, '@view-transition{navigation:auto}' ), 'cross-document view transitions on' );
		$this->assert_true( str_contains( $head, '@media (prefers-reduced-motion: reduce){@view-transition{navigation:none}}' ), 'off for reduced motion' );
	}
}
