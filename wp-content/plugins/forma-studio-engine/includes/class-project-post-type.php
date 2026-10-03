<?php
/**
 * Custom Post Type: forma_project
 */

namespace Forma\Studio\Engine;

defined( 'ABSPATH' ) || exit;

final class Project_Post_Type {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function register() {
		$labels = array(
			'name'               => esc_html__( 'Projects', 'forma-studio-engine' ),
			'singular_name'      => esc_html__( 'Project', 'forma-studio-engine' ),
			'add_new'            => esc_html__( 'Add New', 'forma-studio-engine' ),
			'add_new_item'       => esc_html__( 'Add New Project', 'forma-studio-engine' ),
			'edit_item'          => esc_html__( 'Edit Project', 'forma-studio-engine' ),
			'new_item'           => esc_html__( 'New Project', 'forma-studio-engine' ),
			'view_item'          => esc_html__( 'View Project', 'forma-studio-engine' ),
			'view_items'         => esc_html__( 'View Projects', 'forma-studio-engine' ),
			'search_items'       => esc_html__( 'Search Projects', 'forma-studio-engine' ),
			'not_found'          => esc_html__( 'No projects found.', 'forma-studio-engine' ),
			'not_found_in_trash' => esc_html__( 'No projects found in Trash.', 'forma-studio-engine' ),
			'all_items'          => esc_html__( 'All Projects', 'forma-studio-engine' ),
		);

		$args = array(
			'labels'             => $labels,
			'description'        => esc_html__( 'FORMA architecture and interior projects.', 'forma-studio-engine' ),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_admin_bar'  => true,
			'show_in_rest'       => true,
			'menu_icon'          => 'dashicons-portfolio',
			'menu_position'      => 5,
			'query_var'          => true,
			'rewrite'            => array(
				'slug'       => 'projects',
				'with_front' => false,
			),
			'capability_type'    => 'post',
			'has_archive'         => true,
			'hierarchical'        => false,
			'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
		);

		register_post_type( 'forma_project', $args );
	}
}