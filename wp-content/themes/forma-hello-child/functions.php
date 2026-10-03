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
