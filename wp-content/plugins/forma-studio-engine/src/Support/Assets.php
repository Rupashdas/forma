<?php

namespace Forma\Engine\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Registers every script up front; modules enqueue only what a page actually renders.
 */
final class Assets {

	public const GSAP_VERSION = '3.15.0';

	/** Vendor handle => [file in assets/vendor/gsap/, dependencies]. */
	private const VENDOR = array(
		'forma-gsap'          => array( 'gsap.min.js', array() ),
		'forma-scrolltrigger' => array( 'ScrollTrigger.min.js', array( 'forma-gsap' ) ),
		'forma-splittext'     => array( 'SplitText.min.js', array( 'forma-gsap' ) ),
	);

	public static function register(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'register_assets' ), 5 );
		add_action( 'elementor/frontend/after_register_scripts', array( self::class, 'register_assets' ) );
	}

	public static function register_assets(): void {
		if ( wp_script_is( 'forma-gsap', 'registered' ) ) {
			return;
		}

		foreach ( self::VENDOR as $handle => [ $file, $deps ] ) {
			wp_register_script(
				$handle,
				FORMA_ENGINE_URL . 'assets/vendor/gsap/' . $file,
				$deps,
				self::GSAP_VERSION,
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
		}
	}
}
