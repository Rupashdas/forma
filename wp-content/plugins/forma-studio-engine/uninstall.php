<?php
/**
 * Uninstall script for Forma Studio Engine.
 * Removes CPT, taxonomies, and ACF field groups created by this plugin.
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	return;
}

require_once ABSPATH . 'wp-admin/includes/post.php';

// Delete all projects.
$projects = get_posts(
 array(
	'post_type'   => 'forma_project',
	'post_status'  => array( 'any', 'trash' ),
	'numberposts'  => -1,
 )
);
foreach ( $projects as $project ) {
	 wp_delete_post( $project->ID, true );
}

// Delete ACF field groups created by this plugin.
if ( function_exists( 'acf_get_field_groups' ) ) {
	$groups = acf_get_field_groups();
	foreach ( $groups as $group ) {
		if ( isset( $group['key'] ) && strpos( $group['key'], 'forma_project' ) === 0 ) {
			acf_delete_field_group( $group['key'] );
		}
	}
}