<?php
/**
 * Helper functions for Forma Studio Engine.
 */

namespace Forma\Studio\Engine;

defined( 'ABSPATH' ) || exit;

/**
 * Safely read a project meta value (native WordPress meta, no ACF).
 */
function forma_get_field( $key, $post_id = null, $default = '' ) {
	if ( null === $post_id ) {
		$post_id = get_the_ID();
	}

	$value = get_post_meta( $post_id, $key, true );
	return '' === $value || null === $value ? $default : $value;
}

/**
 * Build a consistent project card data array from a project ID.
 */
function forma_get_project_card( $post_id ) {
	$thumb = get_the_post_thumbnail_url( $post_id, 'forma-grid' );
	if ( ! $thumb ) {
		$thumb = get_the_post_thumbnail_url( $post_id, 'large' );
	}

	$categories = wp_list_pluck(
		get_the_terms( $post_id, 'project_category' ) ?: [],
		'name'
	);

	return array(
		'id'          => $post_id,
		'title'       => get_the_title( $post_id ),
		'link'        => get_permalink( $post_id ),
		'image'       => $thumb ?: '',
		'category'    => $categories ? implode( ', ', $categories ) : '',
		'location'    => forma_get_field( 'location', $post_id ),
		'year'        => forma_get_field( 'year', $post_id ),
		'client'      => forma_get_field( 'client', $post_id ),
	);
}

/**
 * Render a project card template.
 */
function forma_render_project_card( $post_id ) {
	$data = forma_get_project_card( $post_id );
	$path = FORMA_STUDIO_ENGINE_PATH . 'templates/project-card.php';
	if ( file_exists( $path ) ) {
		include $path;
	}
}

/**
 * Get the number of columns for a responsive grid.
 */
function forma_get_columns( $columns ) {
	$map = array(
		'2' => '2',
		'3' => '3',
		'4' => '4',
	);
	return isset( $map[ $columns ] ) ? $map[ $columns ] : '3';
}