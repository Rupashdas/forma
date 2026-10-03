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
}
