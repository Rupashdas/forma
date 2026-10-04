<?php

namespace Forma\Engine\Tests;

defined( 'ABSPATH' ) || exit;

final class ThemeTest extends TestCase {

	public function test_forma_fonts_are_an_elementor_font_group(): void {
		$this->assert_same( 'forma', \Elementor\Fonts::get_font_type( 'Archivo' ) );
		$this->assert_same( 'forma', \Elementor\Fonts::get_font_type( 'Archivo Expanded' ) );
		$this->assert_true( isset( \Elementor\Fonts::get_font_groups()['forma'] ), 'Forma font group exists' );
	}

	public function test_font_files_and_faces_ship_with_the_theme(): void {
		$fonts = get_stylesheet_directory() . '/assets/fonts/';

		$this->assert_true( is_readable( $fonts . 'archivo-latin.woff2' ), 'archivo-latin.woff2 ships' );
		$this->assert_same( array(), glob( $fonts . 'instrument-serif-*.woff2' ), 'Instrument Serif is gone' );

		$css = (string) file_get_contents( get_stylesheet_directory() . '/assets/css/site.css' );
		$this->assert_same( 2, substr_count( $css, '@font-face' ), 'Archivo and Archivo Expanded faces' );
		$this->assert_true( str_contains( $css, 'font-stretch: 62% 125%' ), 'Archivo exposes its width axis' );
	}

	public function test_palette_is_paper_ink_and_signal_blue(): void {
		$css = (string) file_get_contents( get_stylesheet_directory() . '/assets/css/site.css' );

		$tokens = array(
			'--forma-page'           => '#f6f5f1',
			'--forma-raised'         => '#e9e8e3',
			'--forma-ink'            => '#141414',
			'--forma-muted'          => '#55554f',
			'--forma-accent'         => '#2b3bff',
			'--forma-chip'           => '#dce0ff',
			'--forma-deep'           => '#141414',
			'--forma-muted-on-deep'  => '#a3a39c',
			'--forma-accent-on-deep' => '#8e98ff',
		);

		foreach ( $tokens as $token => $hex ) {
			$this->assert_true( str_contains( $css, "{$token}: {$hex};" ), "{$token} is {$hex}" );
		}

		$this->assert_true( str_contains( $css, '.forma-deep' ), 'deep bands switch focus and link colours' );

		foreach ( array( 'basalt', 'sodium', '#f2efe8', '#1e3a2f', 'Bodoni', 'Instrument Serif' ) as $old ) {
			$this->assert_true( ! str_contains( $css, $old ), "old value '{$old}' is gone" );
		}
	}

	public function test_plugin_styles_use_role_tokens_only(): void {
		foreach ( glob( FORMA_ENGINE_PATH . 'assets/css/*.css' ) as $file ) {
			$css = (string) file_get_contents( $file );

			foreach ( array( 'basalt', 'sodium', '--forma-umber', '--forma-stone', '15, 14, 13', '236, 230, 219' ) as $old ) {
				$this->assert_true( ! str_contains( $css, $old ), basename( $file ) . " no longer uses '{$old}'" );
			}
		}
	}

	public function test_archivo_is_preloaded(): void {
		$this->assert_true( function_exists( 'forma_theme_preload_fonts' ), 'forma_theme_preload_fonts() exists' );

		ob_start();
		forma_theme_preload_fonts();
		$html = (string) ob_get_clean();

		$this->assert_same( 1, substr_count( $html, 'rel="preload"' ) );
		$this->assert_true( str_contains( $html, 'archivo-latin.woff2' ), 'Archivo preloaded' );
		$this->assert_true( str_contains( $html, 'crossorigin' ), 'font preloads are CORS' );
	}
}
