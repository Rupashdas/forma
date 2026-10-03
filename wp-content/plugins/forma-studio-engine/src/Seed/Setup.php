<?php

namespace Forma\Engine\Seed;

use Forma\Engine\Projects\Projects;

defined( 'ABSPATH' ) || exit;

/**
 * Site settings the way an owner would set them in wp-admin: name, time zone, image sizes, permalinks and
 * Elementor's performance options. Safe to rerun.
 */
final class Setup {

	/**
	 * Elementor features for a lean front end. Names this Elementor version doesn't know are skipped
	 * (features graduate to always-on and disappear from the list).
	 */
	private const EXPERIMENTS = array( 'e_font_icon_svg', 'e_optimized_markup', 'e_lazyload', 'container', 'nested-elements' );

	public function __construct( private \Closure $log ) {}

	public function run(): void {
		$this->site();
		$this->media();
		$this->permalinks();
		$this->elementor();
	}

	private function site(): void {
		$site = require FORMA_ENGINE_PATH . 'data/site.php';

		update_option( 'blogname', $site['blog']['name'] );
		update_option( 'blogdescription', $site['blog']['description'] );
		update_option( 'timezone_string', 'Europe/Lisbon' );
		update_option( 'date_format', 'j F Y' );
		update_option( 'default_comment_status', 'closed' );
		update_option( 'default_ping_status', 'closed' );

		( $this->log )( 'Site name, time zone and discussion settings.' );
	}

	/**
	 * Medium 480 and large 1600 wide, any height; the 960 size and the 2400 cap come from the Media module.
	 */
	private function media(): void {
		update_option( 'medium_size_w', 480 );
		update_option( 'medium_size_h', 0 );
		update_option( 'large_size_w', 1600 );
		update_option( 'large_size_h', 0 );

		( $this->log )( 'Image sizes: 150, 480, 960, 1600, originals capped at 2400.' );
	}

	private function permalinks(): void {
		global $wp_rewrite;

		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		Projects::register_types();
		flush_rewrite_rules( false );

		( $this->log )( 'Permalinks: /%postname%/, projects under /projects/.' );
	}

	private function elementor(): void {
		update_option( 'elementor_disable_color_schemes', 'yes' );
		update_option( 'elementor_disable_typography_schemes', 'yes' );
		update_option( 'elementor_google_font', '0' );
		update_option( 'elementor_font_display', 'swap' );
		update_option( 'elementor_load_fa4_shim', '' );

		if ( class_exists( \Elementor\Plugin::class ) ) {
			$experiments = \Elementor\Plugin::$instance->experiments;

			foreach ( self::EXPERIMENTS as $feature ) {
				if ( $experiments->get_features( $feature ) ) {
					update_option( $experiments->get_feature_option_key( $feature ), $experiments::STATE_ACTIVE );
				}
			}
		}

		( $this->log )( 'Elementor: kit globals only, Google Fonts off, SVG icons, optimised markup.' );
	}
}
