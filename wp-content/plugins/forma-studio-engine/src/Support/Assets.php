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
		// Keeps imagesLoaded (which Elementor Pro's Loop Grid calls) from loading lazy photographs early; see the file.
		'lazy-images'    => array( 'imagesloaded' ),
	);

	/** Stylesheets up to this many bytes are printed inline rather than linked. */
	private const INLINE_MAX = 6144;

	/**
	 * Footer scripts of Elementor, Elementor Pro, the theme, Contact Form 7 and its deferred-forms add-on that are loaded with `defer`. They sit at the end
	 * of the body as plain scripts, which stops the parser (and so the first paint, on a slow connection) while the 250KB
	 * of them arrive; deferred, they run in the same order, just before DOMContentLoaded, without holding the page up.
	 * jQuery stays in the head: an inline script that follows jQuery UI needs it at once.
	 */
	private const DEFERRED = array(
		'swv',
		'contact-form-7',
		'hello-theme-frontend',
		'elementor-webpack-runtime',
		'elementor-frontend-modules',
		'jquery-ui-core',
		'elementor-frontend',
		'smartmenus',
		'imagesloaded',
		'elementor-pro-webpack-runtime',
		'elementor-pro-frontend',
		'pro-elements-handlers',
		'deferforms-base',
		'deferforms-validate',
		'deferforms-select',
		'forma-lazy-images',
	);

	/** Plugin stylesheet names. */
	private const STYLES = array( 'motion', 'cursor', 'scroll-story', 'project-index', 'before-after', 'marquee', 'next-project', 'smooth-scroll' );

	public static function register(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'register_assets' ), 5 );
		add_action( 'elementor/frontend/after_register_scripts', array( self::class, 'register_assets' ) );
		add_action( 'elementor/frontend/after_register_styles', array( self::class, 'register_assets' ) );
		add_filter( 'script_loader_tag', array( self::class, 'defer_footer_scripts' ), 10, 2 );
		add_action( 'wp_print_footer_scripts', array( self::class, 'lazy_images' ), 1 );
	}

	/**
	 * On any page that loads imagesLoaded, load the small script that stops it fetching lazy photographs early.
	 */
	public static function lazy_images(): void {
		if ( ! is_admin() && wp_script_is( 'imagesloaded', 'enqueued' ) && ! Editor::active() ) {
			self::enqueue( 'lazy-images' );
		}
	}

	/**
	 * Adds `defer` to the footer scripts listed in DEFERRED, on the public site only (never in the editor or its preview).
	 */
	public static function defer_footer_scripts( string $tag, string $handle ): string {
		if ( ! in_array( $handle, self::DEFERRED, true ) || is_admin() || str_contains( $tag, ' defer' ) || Editor::active() ) {
			return $tag;
		}

		return str_replace( ' src=', ' defer src=', $tag );
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
			self::register_style( "forma-{$name}", "assets/css/{$name}.css" );
		}
	}

	/**
	 * Registers a stylesheet of the plugin. One this small costs more as a request than as bytes (every request is a round
	 * trip before the page can paint), so it is printed inline, and only on the pages that enqueue it.
	 */
	public static function register_style( string $handle, string $path ): void {
		$file = FORMA_ENGINE_PATH . $path;

		if ( is_readable( $file ) && filesize( $file ) <= self::INLINE_MAX ) {
			wp_register_style( $handle, false, array(), self::version( $path ) );
			wp_add_inline_style( $handle, (string) file_get_contents( $file ) );

			return;
		}

		wp_register_style( $handle, FORMA_ENGINE_URL . $path, array(), self::version( $path ) );
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
