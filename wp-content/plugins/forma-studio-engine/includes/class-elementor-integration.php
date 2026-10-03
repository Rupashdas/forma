<?php
/**
 * Elementor integration: dynamic tags, controls, cache.
 */

namespace Forma\Studio\Engine;

defined( 'ABSPATH' ) || exit;

final class Elementor_Integration {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function register() {
		// Hook Elementor dynamic tags if available.
		if ( class_exists( 'Elementor\Elementor' ) ) {
			add_action( 'elementor/dynamic_tags/register_tags', array( $this, 'register_dynamic_tags' ) );
		}
	}

	/**
	 * @return void
	 */
	public function register_dynamic_tags( $tags_manager ) {
		if ( ! class_exists( 'Elementor\Core\DynamicTags\Tag' ) ) {
			return;
		}
		// Dynamic tags are optional; reserved for Elementor Pro.
	}

	/**
	 * @param int $post_id
	 */
	public function flush_cache( $post_id ) {
		clean_post_cache( $post_id );
	}
}