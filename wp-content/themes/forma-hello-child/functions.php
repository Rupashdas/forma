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
add_action( 'elementor/page_templates/header-footer/before_content', 'forma_theme_main_open' );
add_action( 'elementor/page_templates/header-footer/after_content', 'forma_theme_main_close' );

/**
 * Site stylesheet and script, versioned by file time so edits bust browser caches. The script drives the header
 * (condense, hide on scroll down, show on scroll up) and is not needed inside Elementor's editor.
 */
function forma_theme_assets(): void {
	$dir = get_stylesheet_directory();
	$uri = get_stylesheet_directory_uri();

	wp_enqueue_style(
		'forma-site',
		$uri . '/assets/css/site.css',
		array(),
		FORMA_THEME_VERSION . '.' . filemtime( $dir . '/assets/css/site.css' )
	);

	if ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
		return;
	}

	wp_enqueue_script(
		'forma-site',
		$uri . '/assets/js/site.js',
		array(),
		FORMA_THEME_VERSION . '.' . filemtime( $dir . '/assets/js/site.js' ),
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);
}

/**
 * Elementor's "Header and footer" page template prints its content bare. Wrapping it in <main id="content"> gives
 * Hello's "Skip to content" link a target and the page a main landmark.
 */
function forma_theme_main_open(): void {
	echo '<main id="content" class="site-main">';
}

function forma_theme_main_close(): void {
	echo '</main>';
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
 * Listing the families in the Forma group keeps Elementor from ever requesting them from Google; the @font-face
 * rules in site.css serve them instead. "Archivo Expanded" is Archivo at its widest width, used by the Label style.
 */
function forma_theme_fonts( array $fonts ): array {
	return array_merge(
		$fonts,
		array(
			'Bodoni Moda'      => 'forma',
			'Archivo'          => 'forma',
			'Archivo Expanded' => 'forma',
		)
	);
}
