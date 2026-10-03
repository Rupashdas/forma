<?php
/**
 * Asset loading — conditional, performant.
 */

namespace Forma\Studio\Engine;

defined( 'ABSPATH' ) || exit;

final class Assets {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend' ) );
		add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'enqueue_editor' ) );
	}

	/**
	 * Determine whether any FORMA assets should load on this request.
	 */
	private function should_load() {
		if ( is_post_type_archive( 'forma_project' ) || is_singular( 'forma_project' ) ) {
			return true;
		}

		// On singular/home pages, check shortcode presence.
		if ( is_home() || is_front_page() || is_page() || is_single() ) {
			$content = get_post_field( 'post_content', get_post_ID() );
			if ( $content && has_shortcode( $content, 'project_' ) ) {
				return true;
			}
			return true; // Load on all pages for cursor + widgets by default.
		}

		return true;
	}

	/**
	 * Enqueue frontend assets only where needed.
	 */
	public function enqueue_frontend() {
		if ( ! $this->should_load() ) {
			return;
		}

		$version = FORMA_STUDIO_ENGINE_VERSION;

		// GSAP + ScrollTrigger.
		if ( $this->should_load_gsap() ) {
			$this->enqueue_gsap();
		}

		// Frontend JS bundle.
		wp_enqueue_script(
			'forma-frontend',
			FORMA_STUDIO_ENGINE_URL . 'assets/js/frontend.js',
			array( 'jquery' ),
			$version,
			true
		);

		// Project grid + filter.
		if ( $this->has_project_shortcode() ) {
			wp_enqueue_script(
				'forma-project-grid',
				FORMA_STUDIO_ENGINE_URL . 'assets/js/project-grid.js',
				array( 'forma-frontend' ),
				$version,
				true
			);
			wp_enqueue_script(
				'forma-project-filter',
				FORMA_STUDIO_ENGINE_URL . 'assets/js/project-filter.js',
				array( 'forma-frontend' ),
				$version,
				true
			);
		}

		// Animated headings.
		if ( $this->has_project_shortcode() || is_singular( 'forma_project' ) || is_front_page() ) {
			wp_enqueue_script(
				'forma-animated-heading',
				FORMA_STUDIO_ENGINE_URL . 'assets/js/animated-heading.js',
				array( 'forma-frontend' ),
				$version,
				true
			);
		}

		// Before / After.
		if ( is_singular( 'forma_project' ) ) {
			wp_enqueue_script(
				'forma-before-after',
				FORMA_STUDIO_ENGINE_URL . 'assets/js/before-after.js',
				array( 'forma-frontend' ),
				$version,
				true
			);
		}

		// Marquee.
		if ( is_front_page() ) {
			wp_enqueue_script(
				'forma-marquee',
				FORMA_STUDIO_ENGINE_URL . 'assets/js/marquee.js',
				array( 'forma-frontend' ),
				$version,
				true
			);
		}

		// Cursor.
		if ( $this->should_load_cursor() ) {
			wp_enqueue_script(
				'forma-cursor',
				FORMA_STUDIO_ENGINE_URL . 'assets/js/cursor.js',
				array( 'forma-frontend' ),
				$version,
				true
			);
		}

		// Page transition.
		if ( $this->should_load_transition() ) {
			wp_enqueue_script(
				'forma-page-transition',
				FORMA_STUDIO_ENGINE_URL . 'assets/js/page-transition.js',
				array( 'forma-frontend' ),
				$version,
				true
			);
		}

		// Widgets CSS.
		wp_enqueue_style(
			'forma-widgets',
			FORMA_STUDIO_ENGINE_URL . 'assets/css/frontend.css',
			array(),
			$version
		);

		// Localize AJAX URL.
		wp_localize_script(
			'forma-frontend',
			'formaSettings',
		 array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'forma_filter_projects' ),
		 )
		);
	}

	/**
	 * Check whether the current page uses a FORMA project shortcode.
	 */
	private function has_project_shortcode() {
		$content = get_post_field( 'post_content', get_post_ID() );
		return $content && has_shortcode( $content, 'project_' );
	}

	/**
	 * @return bool
	 */
	private function should_load_gsap() {
		if ( ! is_user_logged_in() && $this->is_disabled_for_guests() ) {
			return false;
		}
		return apply_filters( 'forma_load_gsap', true );
	}

	/**
	 * @return bool
	 */
	private function should_load_cursor() {
		if ( wp_is_mobile() ) {
			return false;
		}
		return apply_filters( 'forma_enable_cursor', true );
	}

	/**
	 * @return bool
	 */
	private function should_load_transition() {
		return apply_filters( 'forma_enable_page_transition', false );
	}

	/**
	 * @return bool
	 */
	private function is_disabled_for_guests() {
		return (bool) apply_filters( 'forma_disable_gsap_guests', false );
	}

	/**
	 * Enqueue GSAP + ScrollTrigger from CDN.
	 */
	private function enqueue_gsap() {
		$gsap_version = '3.12.5';

		if ( ! wp_script_is( 'gsap', 'enqueued' ) ) {
			wp_enqueue_script(
				'gsap',
				'https://cdnjs.cloudflare.com/ajax/libs/gsap/' . $gsap_version . '/gsap.min.js',
				array(),
				$gsap_version,
				true
			);
		}

		$handle = 'gsap-scrolltrigger';
		if ( ! wp_script_is( $handle, 'enqueued' ) ) {
			wp_enqueue_script(
				$handle,
				'https://cdnjs.cloudflare.com/ajax/libs/gsap/' . $gsap_version . '/ScrollTrigger.min.js',
				array( 'gsap' ),
				$gsap_version,
				true
			);
		}
	}

	/**
	 * Editor-only assets.
	 */
	public function enqueue_editor() {
		// No custom editor assets needed; widgets use Elementor's native controls.
	}
}