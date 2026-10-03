<?php

namespace Forma\Engine\Projects;

use Forma\Engine\Contracts\Module;

defined( 'ABSPATH' ) || exit;

/**
 * The Projects post type and its two taxonomies. A project's year is its post date (the completion date);
 * everything else about a project is composed in its Elementor body, so there are no custom fields.
 */
final class Projects implements Module {

	public const POST_TYPE    = 'forma_project';
	public const TYPE_TAX     = 'project_type';
	public const LOCATION_TAX = 'project_location';

	public static function id(): string {
		return 'projects';
	}

	public function is_available(): bool {
		return true;
	}

	public function register(): void {
		add_action( 'init', array( self::class, 'register_types' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( ProjectNumber::class, 'flush' ) );
		add_action( 'deleted_post', array( ProjectNumber::class, 'flush' ) );
	}

	/**
	 * Taxonomies are registered before the post type so their /projects/type/… and /projects/location/… rewrite
	 * rules sit above the post type's attachment rules, which would otherwise swallow those URLs.
	 */
	public static function register_types(): void {
		register_taxonomy(
			self::TYPE_TAX,
			self::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Project types', 'forma-studio-engine' ),
					'singular_name' => __( 'Project type', 'forma-studio-engine' ),
					'all_items'     => __( 'All types', 'forma-studio-engine' ),
					'edit_item'     => __( 'Edit type', 'forma-studio-engine' ),
					'add_new_item'  => __( 'Add new type', 'forma-studio-engine' ),
					'search_items'  => __( 'Search types', 'forma-studio-engine' ),
					'not_found'     => __( 'No types found.', 'forma-studio-engine' ),
					'menu_name'     => __( 'Types', 'forma-studio-engine' ),
				),
				'hierarchical'      => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array(
					'slug'       => 'projects/type',
					'with_front' => false,
				),
			)
		);

		register_taxonomy(
			self::LOCATION_TAX,
			self::POST_TYPE,
			array(
				'labels'            => array(
					'name'                       => __( 'Locations', 'forma-studio-engine' ),
					'singular_name'              => __( 'Location', 'forma-studio-engine' ),
					'all_items'                  => __( 'All locations', 'forma-studio-engine' ),
					'edit_item'                  => __( 'Edit location', 'forma-studio-engine' ),
					'add_new_item'               => __( 'Add new location', 'forma-studio-engine' ),
					'search_items'               => __( 'Search locations', 'forma-studio-engine' ),
					'not_found'                  => __( 'No locations found.', 'forma-studio-engine' ),
					'separate_items_with_commas' => __( 'One location, written as "City, Country".', 'forma-studio-engine' ),
				),
				'hierarchical'      => false,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array(
					'slug'       => 'projects/location',
					'with_front' => false,
				),
			)
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'        => array(
					'name'                  => __( 'Projects', 'forma-studio-engine' ),
					'singular_name'         => __( 'Project', 'forma-studio-engine' ),
					'add_new_item'          => __( 'Add new project', 'forma-studio-engine' ),
					'edit_item'             => __( 'Edit project', 'forma-studio-engine' ),
					'new_item'              => __( 'New project', 'forma-studio-engine' ),
					'view_item'             => __( 'View project', 'forma-studio-engine' ),
					'view_items'            => __( 'View projects', 'forma-studio-engine' ),
					'search_items'          => __( 'Search projects', 'forma-studio-engine' ),
					'not_found'             => __( 'No projects found.', 'forma-studio-engine' ),
					'not_found_in_trash'    => __( 'No projects found in the bin.', 'forma-studio-engine' ),
					'all_items'             => __( 'All projects', 'forma-studio-engine' ),
					'archives'              => __( 'Projects', 'forma-studio-engine' ),
					'featured_image'        => __( 'Project image', 'forma-studio-engine' ),
					'set_featured_image'    => __( 'Set project image', 'forma-studio-engine' ),
					'remove_featured_image' => __( 'Remove project image', 'forma-studio-engine' ),
					'use_featured_image'    => __( 'Use as project image', 'forma-studio-engine' ),
					'item_published'        => __( 'Project published.', 'forma-studio-engine' ),
					'item_updated'          => __( 'Project updated.', 'forma-studio-engine' ),
				),
				'description'   => __( 'Buildings, interiors and objects by the studio. The publish date is the completion date.', 'forma-studio-engine' ),
				'public'        => true,
				'show_in_rest'  => true,
				'has_archive'   => 'projects',
				'rewrite'       => array(
					'slug'       => 'projects',
					'with_front' => false,
				),
				'menu_position' => 5,
				'menu_icon'     => 'dashicons-building',
				'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'elementor' ),
			)
		);
	}
}
