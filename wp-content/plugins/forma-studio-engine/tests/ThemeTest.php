<?php

namespace Forma\Engine\Tests;

defined( 'ABSPATH' ) || exit;

final class ThemeTest extends TestCase {

	public function test_forma_fonts_are_an_elementor_font_group(): void {
		$this->assert_same( 'forma', \Elementor\Fonts::get_font_type( 'Instrument Serif' ) );
		$this->assert_same( 'forma', \Elementor\Fonts::get_font_type( 'Archivo' ) );
		$this->assert_true( isset( \Elementor\Fonts::get_font_groups()['forma'] ), 'Forma font group exists' );
	}

	public function test_font_files_and_faces_ship_with_the_theme(): void {
		foreach ( array( 'instrument-serif-latin', 'instrument-serif-italic-latin', 'archivo-latin' ) as $font ) {
			$this->assert_true( is_readable( get_stylesheet_directory() . "/assets/fonts/{$font}.woff2" ), "{$font}.woff2 ships" );
		}

		$css = (string) file_get_contents( get_stylesheet_directory() . '/assets/css/site.css' );
		$this->assert_same( 4, substr_count( $css, '@font-face' ), 'four @font-face rules (Archivo Expanded included)' );
		$this->assert_true( str_contains( $css, 'font-stretch: 62% 125%' ), 'Archivo exposes its width axis' );
	}

	public function test_palette_is_chalk_and_bottle_green(): void {
		$css = (string) file_get_contents( get_stylesheet_directory() . '/assets/css/site.css' );

		$tokens = array(
			'--forma-page'           => '#f2efe8',
			'--forma-raised'         => '#e7e3d9',
			'--forma-ink'            => '#161917',
			'--forma-muted'          => '#575c57',
			'--forma-accent'         => '#7d5c1d',
			'--forma-deep'           => '#1e3a2f',
			'--forma-muted-on-deep'  => '#a9b8ae',
			'--forma-accent-on-deep' => '#d2ae63',
		);

		foreach ( $tokens as $token => $hex ) {
			$this->assert_true( str_contains( $css, "{$token}: {$hex};" ), "{$token} is {$hex}" );
		}

		$this->assert_true( str_contains( $css, 'color-scheme: light' ), 'native controls follow the light page' );
		$this->assert_true( str_contains( $css, '.forma-deep' ), 'deep sections switch focus and link colours' );

		foreach ( array( 'basalt', 'bone', 'sodium', '--forma-umber', '--forma-stone', 'plaster', 'redline', 'Bodoni' ) as $old ) {
			$this->assert_true( ! str_contains( $css, $old ), "old token '{$old}' is gone" );
		}
	}

	public function test_plugin_styles_use_role_tokens_only(): void {
		foreach ( glob( FORMA_ENGINE_PATH . 'assets/css/*.css' ) as $file ) {
			$css = (string) file_get_contents( $file );

			foreach ( array( 'basalt', 'bone', 'sodium', '--forma-umber', '--forma-stone', '15, 14, 13', '236, 230, 219' ) as $old ) {
				$this->assert_true( ! str_contains( $css, $old ), basename( $file ) . " no longer uses '{$old}'" );
			}
		}
	}

	public function test_roman_faces_are_preloaded(): void {
		$this->assert_true( function_exists( 'forma_theme_preload_fonts' ), 'forma_theme_preload_fonts() exists' );

		ob_start();
		forma_theme_preload_fonts();
		$html = (string) ob_get_clean();

		$this->assert_same( 2, substr_count( $html, 'rel="preload"' ) );
		$this->assert_true( str_contains( $html, 'instrument-serif-latin.woff2' ) && str_contains( $html, 'archivo-latin.woff2' ), 'Instrument Serif and Archivo preloaded' );
		$this->assert_true( str_contains( $html, 'crossorigin' ), 'font preloads are CORS' );
	}
}
