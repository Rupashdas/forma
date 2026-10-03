<?php
/**
 * Forma Hello Child — presentation only.
 *
 * Post types, widgets, motion, media and SEO live in the Forma Studio Engine plugin;
 * page layouts live in Elementor.
 */

defined( 'ABSPATH' ) || exit;

define( 'FORMA_THEME_VERSION', '1.0.0' );

add_action( 'wp_enqueue_scripts', 'forma_theme_assets', 20 );
add_action( 'wp_head', 'forma_theme_preload_fonts', 1 );
add_filter( 'elementor/fonts/groups', 'forma_theme_font_group' );
add_filter( 'elementor/fonts/additional_fonts', 'forma_theme_fonts' );

/**
 * Site stylesheet, versioned by file time so edits bust browser caches.
 */
function forma_theme_assets(): void {
	$path = '/assets/css/site.css';

	wp_enqueue_style(
		'forma-site',
		get_stylesheet_directory_uri() . $path,
		array(),
		FORMA_THEME_VERSION . '.' . filemtime( get_stylesheet_directory() . $path )
	);
}

/**
 * Preload the two roman faces every page sets above the fold; the italic loads on demand.
 */
function forma_theme_preload_fonts(): void {
	foreach ( array( 'bodoni-moda-latin', 'archivo-latin' ) as $font ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( get_stylesheet_directory_uri() . "/assets/fonts/{$font}.woff2" )
		);
	}
}

/**
 * A "Forma" group in Elementor's font picker for the self-hosted families.
 */
function forma_theme_font_group( array $groups ): array {
	return array( 'forma' => __( 'Forma (self-hosted)', 'forma-hello-child' ) ) + $groups;
}

/**
 * Listing both families in the Forma group keeps Elementor from ever requesting them from Google;
 * the @font-face rules in site.css serve them instead.
 */
function forma_theme_fonts( array $fonts ): array {
	return array_merge(
		$fonts,
		array(
			'Bodoni Moda' => 'forma',
			'Archivo'     => 'forma',
		)
	);
}
