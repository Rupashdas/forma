<?php
/**
 * Main plugin class — orchestrates initialization.
 */

namespace Forma\Studio\Engine;

defined( 'ABSPATH' ) || exit;

final class Main {

	/**
	 * @var Main|null
	 */
	private static $instance = null;

	/**
	 * @return Main
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->load_components();
		add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ) );
		add_action( 'init', array( $this, 'on_init' ) );
		add_action( 'elementor/widgets/widgets_registered', array( $this, 'register_widgets' ) );
		add_action( 'elementor/editor/after_save', array( $this, 'flush_elementor_cache' ), 10, 2 );
	}

	/**
	 * Load all component classes.
	 */
	private function load_components() {
		Project_Post_Type::instance();
		Project_Taxonomy::instance();
		Assets::instance();
		Elementor_Integration::instance();
		AJAX::instance();

		// Widgets.
		Project_Grid::instance();
		Project_Meta::instance();
		Project_Gallery::instance();
		Before_After::instance();
		Animated_Heading::instance();
		Marquee::instance();
		Project_Filter::instance();
	}

	/**
	 * @return void
	 */
	public function on_plugins_loaded() {
		// Hook dynamic tags / controls here if Elementor Pro is active.
		if ( class_exists( 'Elementor\Elementor' ) ) {
			Elementor_Integration::instance()->register();
		}
	}

	/**
	 * @return void
	 */
	public function on_init() {
		Project_Post_Type::instance()->register();
		Project_Taxonomy::instance()->register();

		// Register shortcodes.
		add_shortcode( 'project_grid', array( Project_Grid::instance(), 'render' ) );
		add_shortcode( 'project_meta', array( Project_Meta::instance(), 'render' ) );
		add_shortcode( 'project_gallery', array( Project_Gallery::instance(), 'render' ) );
		add_shortcode( 'before_after', array( Before_After::instance(), 'render' ) );
		add_shortcode( 'project_filter', array( Project_Filter::instance(), 'render' ) );
	}

	/**
	 * Register custom Elementor widgets.
	 */
	public function register_widgets( $widgets_manager ) {
		$widgets = array(
			'Project_Grid'       => Project_Grid::class,
			'Project_Meta'       => Project_Meta::class,
			'Project_Gallery'    => Project_Gallery::class,
			'Before_After'       => Before_After::class,
			'Animated_Heading'   => Animated_Heading::class,
			'Marquee'            => Marquee::class,
			'Project_Filter'     => Project_Filter::class,
		);

		foreach ( $widgets as $class_name => $class ) {
			if ( class_exists( $class ) ) {
				$widgets_manager->register_widget_type( new $class() );
			}
		}
	}

	/**
	 * @return void
	 */
	public function flush_elementor_cache( $post_id, $data ) {
		if ( 'forma_project' === get_post_type( $post_id ) ) {
			Elementor_Integration::instance()->flush_cache( $post_id );
		}
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}
}