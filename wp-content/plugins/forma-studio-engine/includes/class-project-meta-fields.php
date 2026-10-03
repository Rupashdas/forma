<?php
/**
 * Project meta fields — native WordPress register_post_meta.
 * No ACF dependency. Fields are exposed to the REST API and used by widgets.
 */

namespace Forma\Studio\Engine;

defined( 'ABSPATH' ) || exit;

final class Project_Meta_Fields {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	public function register() {
		$meta = $this->get_schema();

		foreach ( $meta as $key => $args ) {
			register_post_meta( 'forma_project', $key, $args );
		}
	}

	/**
	 * @return array
	 */
	public function get_schema() {
		$strings = array(
			'client', 'location', 'year', 'area', 'type', 'status',
			'architect', 'designer', 'project_url', 'short_description',
			'long_description', 'challenge', 'approach', 'result', 'awards',
		);

		$schema = array();
		foreach ( $strings as $key ) {
			$schema[ $key ] = array(
				'type'         => 'string',
				'description'  => sprintf( esc_html__( 'Project %s', 'forma-studio-engine' ), $key ),
				'single'       => true,
				'show_in_rest' => true,
				'default'      => '',
			);
		}

		$schema['featured'] = array(
			'type'         => 'boolean',
			'single'       => true,
			'show_in_rest' => true,
			'default'      => false,
		);

		$schema['hero_image'] = array(
			'type'         => 'object',
			'single'       => true,
			'show_in_rest' => true,
			'default'      => array(),
		);

		$schema['project_gallery'] = array(
			'type'         => 'array',
			'single'       => true,
			'show_in_rest' => true,
			'default'      => array(),
			'items'        => array(
				'type' => 'object',
			),
		);

		$schema['before_image'] = array(
			'type'         => 'object',
			'single'       => true,
			'show_in_rest' => true,
			'default'      => array(),
		);

		$schema['after_image'] = array(
			'type'         => 'object',
			'single'       => true,
			'show_in_rest' => true,
			'default'      => array(),
		);

		return $schema;
	}

	/**
	 * Helper to read a project meta value safely.
	 */
	public static function get_meta( $key, $post_id = 0, $default = '' ) {
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		$value = get_post_meta( $post_id, $key, true );
		return '' === $value || null === $value ? $default : $value;
	}
}

Project_Meta_Fields::instance();