<?php

namespace Forma\Engine\Tests;

use Forma\Engine\Seed\Setup;

defined( 'ABSPATH' ) || exit;

final class SetupTest extends TestCase {

	public function test_setup_configures_site_media_permalinks_and_elementor(): void {
		( new Setup( static function ( string $message ): void {} ) )->run();

		$this->assert_same( 'FORMA', get_option( 'blogname' ) );
		$this->assert_same( 'Europe/Lisbon', get_option( 'timezone_string' ) );
		$this->assert_same( '/%postname%/', get_option( 'permalink_structure' ) );
		$this->assert_same( 'closed', get_option( 'default_comment_status' ) );
		$this->assert_same( 480, (int) get_option( 'medium_size_w' ) );
		$this->assert_same( 0, (int) get_option( 'medium_size_h' ) );
		$this->assert_same( 1600, (int) get_option( 'large_size_w' ) );
		$this->assert_same( 0, (int) get_option( 'large_size_h' ) );
		$this->assert_same( '0', get_option( 'elementor_google_font' ) );
		$this->assert_same( 'yes', get_option( 'elementor_disable_color_schemes' ) );
		$this->assert_same( 'yes', get_option( 'elementor_disable_typography_schemes' ) );
		$this->assert_same( 'swap', get_option( 'elementor_font_display' ) );
	}

	public function test_setup_turns_on_known_elementor_features(): void {
		( new Setup( static function ( string $message ): void {} ) )->run();

		$experiments = \Elementor\Plugin::$instance->experiments;

		foreach ( array( 'e_font_icon_svg', 'e_optimized_markup' ) as $feature ) {
			if ( $experiments->get_features( $feature ) ) {
				$this->assert_same( 'active', get_option( $experiments->get_feature_option_key( $feature ) ), "{$feature} is active" );
			}
		}
	}

	public function test_cli_command_is_registered(): void {
		$this->assert_true( array_key_exists( 'forma', \WP_CLI::get_root_command()->get_subcommands() ), '`wp forma` is registered' );
	}
}
