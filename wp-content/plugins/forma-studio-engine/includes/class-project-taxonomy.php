<?php
/**
 * Taxonomies: project_category, project_location
 */

namespace Forma\Studio\Engine;

defined( 'ABSPATH' ) || exit;

final class Project_Taxonomy {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function register() {
		$this->register_category();
		$this->register_location();
	}

	private function register_category() {
		$labels = array(
			'name'                       => esc_html__( 'Project Categories', 'forma-studio-engine' ),
			'singular_name'              => esc_html__( 'Project Category', 'forma-studio-engine' ),
			'search_items'               => esc_html__( 'Search Categories', 'forma-studio-engine' ),
			'all_items'                  => esc_html__( 'All Categories', 'forma-studio-engine' ),
			'parent_item'                => esc_html__( 'Parent Category', 'forma-studio-engine' ),
			'parent_item_colon'          => esc_html__( 'Parent Category:', 'forma-studio-engine' ),
			'edit_item'                  => esc_html__( 'Edit Category', 'forma-studio-engine' ),
			'update_item'                => esc_html__( 'Update Category', 'forma-studio-engine' ),
			'add_new_item'               => esc_html__( 'Add New Category', 'forma-studio-engine' ),
			'new_item_name'              => esc_html__( 'New Category Name', 'forma-studio-engine' ),
		);

		register_taxonomy(
			'project_category',
			'forma_project',
			array(
				'labels'            => $labels,
				'description'       => esc_html__( 'Project categories for FORMA.', 'forma-studio-engine' ),
				'public'            => true,
				'publicly_queryable'=> true,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'show_in_quick_edit'=> true,
				'show_admin_column' => true,
				'rewrite'           => array(
					'slug'       => 'projects/category',
					'with_front' => false,
				),
				'hierarchical'      => true,
			)
		);
	}

	private function register_location() {
		$labels = array(
			'name'                       => esc_html__( 'Project Locations', 'forma-studio-engine' ),
			'singular_name'              => esc_html__( 'Project Location', 'forma-studio-engine' ),
		);

		register_taxonomy(
			'project_location',
			'forma_project',
			array(
				'labels'            => $labels,
				'description'       => esc_html__( 'Project locations for FORMA.', 'forma-studio-engine' ),
				'public'            => true,
				'publicly_queryable'=> true,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array(
					'slug'       => 'projects/location',
					'with_front' => false,
				),
				'hierarchical'      => true,
			)
		);
	}
}