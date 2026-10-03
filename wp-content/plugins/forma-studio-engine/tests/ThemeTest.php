<?php

namespace Forma\Engine\Tests;

defined( 'ABSPATH' ) || exit;

final class ThemeTest extends TestCase {

	public function test_forma_fonts_are_an_elementor_font_group(): void {
		$this->assert_same( 'forma', \Elementor\Fonts::get_font_type( 'Bodoni Moda' ) );
		$this->assert_same( 'forma', \Elementor\Fonts::get_font_type( 'Archivo' ) );
		$this->assert_true( isset( \Elementor\Fonts::get_font_groups()['forma'] ), 'Forma font group exists' );
	}

	public function test_font_files_and_faces_ship_with_the_theme(): void {
		foreach ( array( 'bodoni-moda-latin', 'bodoni-moda-italic-latin', 'archivo-latin' ) as $font ) {
			$this->assert_true( is_readable( get_stylesheet_directory() . "/assets/fonts/{$font}.woff2" ), "{$font}.woff2 ships" );
		}

		$css = (string) file_get_contents( get_stylesheet_directory() . '/assets/css/site.css' );
		$this->assert_same( 3, substr_count( $css, '@font-face' ), 'three @font-face rules' );
		$this->assert_true( str_contains( $css, 'font-stretch: 62% 125%' ), 'Archivo exposes its width axis' );
	}

	public function test_palette_is_basalt(): void {
		$css = (string) file_get_contents( get_stylesheet_directory() . '/assets/css/site.css' );

		$tokens = array(
			'--forma-basalt' => '#0f0e0d',
			'--forma-raised' => '#1a1917',
			'--forma-bone'   => '#ece6db',
			'--forma-stone'  => '#9b9488',
			'--forma-umber'  => '#6b655c',
			'--forma-sodium' => '#e8a33d',
		);

		foreach ( $tokens as $token => $hex ) {
			$this->assert_true( str_contains( $css, "{$token}: {$hex};" ), "{$token} is {$hex}" );
		}

		foreach ( array( 'plaster', 'graphite', 'redline', '--forma-ink', '--forma-paper' ) as $old ) {
			$this->assert_true( ! str_contains( $css, $old ), "old token '{$old}' is gone" );
		}
	}

	public function test_roman_faces_are_preloaded(): void {
		$this->assert_true( function_exists( 'forma_theme_preload_fonts' ), 'forma_theme_preload_fonts() exists' );

		ob_start();
		forma_theme_preload_fonts();
		$html = (string) ob_get_clean();

		$this->assert_same( 2, substr_count( $html, 'rel="preload"' ) );
		$this->assert_true( str_contains( $html, 'bodoni-moda-latin.woff2' ) && str_contains( $html, 'archivo-latin.woff2' ), 'Bodoni Moda and Archivo preloaded' );
		$this->assert_true( str_contains( $html, 'crossorigin' ), 'font preloads are CORS' );
	}
}
