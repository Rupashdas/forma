<?php

namespace Forma\Engine\Tests;

use Forma\Engine\Support\Assets;

defined( 'ABSPATH' ) || exit;

final class AssetsTest extends TestCase {

	public function test_gsap_is_registered_but_not_enqueued(): void {
		Assets::register_assets();

		$expected = array(
			'forma-gsap'          => array(),
			'forma-scrolltrigger' => array( 'forma-gsap' ),
			'forma-splittext'     => array( 'forma-gsap' ),
		);

		foreach ( $expected as $handle => $deps ) {
			$this->assert_true( wp_script_is( $handle, 'registered' ), "{$handle} is registered" );
			$this->assert_true( ! wp_script_is( $handle, 'enqueued' ), "{$handle} is not enqueued by default" );

			$script = wp_scripts()->registered[ $handle ];
			$this->assert_same( $deps, $script->deps, "{$handle} deps" );
			$this->assert_same( '3.15.0', $script->ver, "{$handle} version" );
			$this->assert_same( 'defer', $script->extra['strategy'] ?? null, "{$handle} is deferred" );
			$this->assert_true( is_readable( FORMA_ENGINE_PATH . 'assets/vendor/gsap/' . basename( $script->src ) ), "{$handle} file ships" );
		}
	}

	public function test_module_assets_are_registered_with_their_dependencies(): void {
		Assets::register_assets();

		$this->assert_same( array( 'forma-gsap', 'forma-scrolltrigger', 'forma-splittext' ), wp_scripts()->registered['forma-motion']->deps ?? null );
		$this->assert_same( array( 'forma-gsap', 'forma-scrolltrigger' ), wp_scripts()->registered['forma-scroll-story']->deps ?? null );

		foreach ( array( 'motion', 'cursor', 'scroll-story', 'project-index', 'before-after', 'marquee', 'next-project' ) as $name ) {
			$this->assert_true( wp_style_is( "forma-{$name}", 'registered' ), "forma-{$name} style registered" );
			$this->assert_true( is_readable( FORMA_ENGINE_PATH . "assets/css/{$name}.css" ), "{$name}.css ships" );
		}

		Assets::enqueue( 'marquee' );
		$this->assert_true( wp_style_is( 'forma-marquee', 'enqueued' ), 'enqueue() enqueues the style' );
		$this->assert_true( ! wp_script_is( 'forma-marquee', 'registered' ), 'marquee is CSS-only' );
	}
}
