<?php

namespace Forma\Engine\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Registers every script and style up front; modules and widgets enqueue only what a page actually renders.
 *
 * Plugin assets live in assets/js/{name}.js and assets/css/{name}.css and get the handle "forma-{name}".
 */
final class Assets {

	public const GSAP_VERSION = '3.15.0';

	/** Version of the vendored Lenis build in assets/vendor/lenis/ (MIT, UMD, global `Lenis`). */
	public const LENIS_VERSION = '1.3.26';

	/** Vendor handle => [file in assets/vendor/gsap/, dependencies]. */
	private const VENDOR = array(
		'forma-gsap'          => array( 'gsap.min.js', array() ),
		'forma-scrolltrigger' => array( 'ScrollTrigger.min.js', array( 'forma-gsap' ) ),
		'forma-splittext'     => array( 'SplitText.min.js', array( 'forma-gsap' ) ),
	);

	/** Plugin script name => dependency handles. */
	private const SCRIPTS = array(
		'motion'         => array( 'forma-gsap', 'forma-scrolltrigger', 'forma-splittext' ),
		'cursor'         => array(),
		'scroll-story'   => array( 'forma-gsap', 'forma-scrolltrigger' ),
		'project-index'  => array(),
		'before-after'   => array(),
		'smooth-scroll'  => array( 'forma-lenis', 'forma-gsap', 'forma-scrolltrigger' ),
		// The Process page's stage readout, and the opening of a Nested Accordion item by link (see Elementor\Module).
		'process'        => array(),
		'accordion-link' => array(),
	);

	/** Plugin stylesheet names. */
	private const STYLES = array( 'motion', 'cursor', 'scroll-story', 'project-index', 'before-after', 'marquee', 'next-project', 'smooth-scroll' );

	public static function register(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'register_assets' ), 5 );
		add_action( 'elementor/frontend/after_register_scripts', array( self::class, 'register_assets' ) );
		add_action( 'elementor/frontend/after_register_styles', array( self::class, 'register_assets' ) );
	}

	public static function register_assets(): void {
		if ( wp_script_is( 'forma-gsap', 'registered' ) ) {
			return;
		}

		foreach ( self::VENDOR as $handle => [ $file, $deps ] ) {
			wp_register_script( $handle, FORMA_ENGINE_URL . 'assets/vendor/gsap/' . $file, $deps, self::GSAP_VERSION, self::footer() );
		}

		wp_register_script( 'forma-lenis', FORMA_ENGINE_URL . 'assets/vendor/lenis/lenis.min.js', array(), self::LENIS_VERSION, self::footer() );

		foreach ( self::SCRIPTS as $name => $deps ) {
			wp_register_script( "forma-{$name}", FORMA_ENGINE_URL . "assets/js/{$name}.js", $deps, self::version( "assets/js/{$name}.js" ), self::footer() );
		}

		foreach ( self::STYLES as $name ) {
			wp_register_style( "forma-{$name}", FORMA_ENGINE_URL . "assets/css/{$name}.css", array(), self::version( "assets/css/{$name}.css" ) );
		}
	}

	/**
	 * Enqueue the style and (if it has one) the script of each named asset.
	 */
	public static function enqueue( string ...$names ): void {
		self::register_assets();

		foreach ( $names as $name ) {
			if ( wp_style_is( "forma-{$name}", 'registered' ) ) {
				wp_enqueue_style( "forma-{$name}" );
			}

			if ( wp_script_is( "forma-{$name}", 'registered' ) ) {
				wp_enqueue_script( "forma-{$name}" );
			}
		}
	}

	/**
	 * Plugin version plus file time, so edited files bust browser caches.
	 */
	public static function version( string $path ): string {
		$file = FORMA_ENGINE_PATH . $path;

		return FORMA_ENGINE_VERSION . ( is_readable( $file ) ? '.' . filemtime( $file ) : '' );
	}

	private static function footer(): array {
		return array(
			'in_footer' => true,
			'strategy'  => 'defer',
		);
	}
}
