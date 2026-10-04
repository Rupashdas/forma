<?php

namespace Forma\Engine\Model;

use Forma\Engine\Projects\ProjectNumber;
use Forma\Engine\Projects\Projects;

defined( 'ABSPATH' ) || exit;

/**
 * Study-model data: the recipes in data/models.php, normalised, and the model each project shows. A project's own
 * model is the volumes of the first `forma-study-model` widget in its Elementor body whose source is "custom";
 * a project without one shows the recipe for its slug.
 *
 * A volume is array{kind, w, h, d, x, y, z, rot, material, part, stage}; see data/models.php for the units.
 */
final class Models {

	public const KINDS     = array( 'box', 'gable', 'cylinder', 'slab', 'wire' );
	public const MATERIALS = array( 'foam', 'shade', 'ink', 'glass', 'wire' );
	public const RECIPES   = array( 'studio', 'lisbon-block', 'plot', 'process' );

	/** Most volumes a model may have; the runtime budget assumes it. */
	public const MAX_VOLUMES = 30;

	/** @var array<string, array>|null Recipe key => recipe, normalised, loaded once per request. */
	private static ?array $recipes = null;

	/** @var array<int, string>|null Project ID => title, for editor controls. */
	private static ?array $options = null;

	/**
	 * A recipe by project slug or recipe name.
	 *
	 * @return array{volumes: list<array>, camera?: array{distance?: float}}|null
	 */
	public static function recipe( string $key ): ?array {
		if ( null === self::$recipes ) {
			$file          = FORMA_ENGINE_PATH . 'data/models.php';
			$data          = is_readable( $file ) ? include $file : array();
			self::$recipes = array();

			foreach ( (array) $data as $name => $recipe ) {
				$normalised = array( 'volumes' => self::normalise( (array) ( $recipe['volumes'] ?? array() ) ) );
				$distance   = (float) ( $recipe['camera']['distance'] ?? 0 );

				if ( $distance > 0 ) {
					$normalised['camera'] = array( 'distance' => round( min( 3, max( 0.3, $distance ) ), 3 ) );
				}

				self::$recipes[ (string) $name ] = $normalised;
			}
		}

		return self::$recipes[ $key ] ?? null;
	}

	/**
	 * Cast a list of volumes (from a recipe or from widget settings) into the shape the runtime reads: numbers cast
	 * and sizes clamped to 0.02–40, kind and material limited to the allowed sets, at most 30 volumes.
	 *
	 * @param array $volumes Raw volumes.
	 * @return list<array>
	 */
	public static function normalise( array $volumes ): array {
		$out = array();

		foreach ( $volumes as $volume ) {
			if ( count( $out ) >= self::MAX_VOLUMES ) {
				break;
			}

			if ( ! is_array( $volume ) ) {
				continue;
			}

			$kind     = in_array( $volume['kind'] ?? '', self::KINDS, true ) ? $volume['kind'] : 'box';
			$material = in_array( $volume['material'] ?? '', self::MATERIALS, true ) ? $volume['material'] : 'foam';
			$w        = self::size( $volume['w'] ?? 1 );

			if ( 'wire' === $kind ) {
				$material = 'wire';
			}

			$out[] = array(
				'kind'     => $kind,
				'w'        => $w,
				'h'        => self::size( $volume['h'] ?? 1 ),
				'd'        => 'cylinder' === $kind ? $w : self::size( $volume['d'] ?? 1 ),
				'x'        => self::coordinate( $volume['x'] ?? 0 ),
				'y'        => self::coordinate( $volume['y'] ?? 0 ),
				'z'        => self::coordinate( $volume['z'] ?? 0 ),
				'rot'      => round( fmod( (float) ( $volume['rot'] ?? 0 ), 360 ), 2 ),
				'material' => $material,
				'part'     => mb_substr( sanitize_text_field( (string) ( $volume['part'] ?? '' ) ), 0, 40 ),
				'stage'    => (int) min( 6, max( 0, (int) ( $volume['stage'] ?? 0 ) ) ),
			);
		}

		return $out;
	}

	/**
	 * The model a project shows: its own custom volumes, else the recipe for its slug.
	 *
	 * @return array{id: int, slug: string, title: string, url: string, number: string, volumes: list<array>, camera?: array{distance?: float}}|null
	 */
	public static function for_project( int $post_id ): ?array {
		$post = get_post( $post_id );

		if ( ! $post || Projects::POST_TYPE !== $post->post_type ) {
			return null;
		}

		$volumes = self::custom_volumes( $post_id );
		$camera  = array();

		if ( ! $volumes ) {
			$recipe  = self::recipe( $post->post_name );
			$volumes = $recipe['volumes'] ?? array();
			$camera  = $recipe['camera'] ?? array();
		}

		if ( ! $volumes ) {
			return null;
		}

		$model = array(
			'id'      => (int) $post->ID,
			'slug'    => (string) $post->post_name,
			'title'   => get_the_title( $post ),
			'url'     => (string) get_permalink( $post ),
			'number'  => ProjectNumber::for_post( (int) $post->ID ),
			'volumes' => $volumes,
		);

		if ( $camera ) {
			$model['camera'] = $camera;
		}

		return $model;
	}

	/**
	 * Every published project with a model, newest first, keyed by post ID.
	 *
	 * @return array<int, array>
	 */
	public static function all_projects(): array {
		$ids = get_posts(
			array(
				'post_type'        => Projects::POST_TYPE,
				'post_status'      => 'publish',
				'posts_per_page'   => -1,
				'orderby'          => array(
					'date' => 'DESC',
					'ID'   => 'DESC',
				),
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => true,
			)
		);
		$all = array();

		foreach ( $ids as $id ) {
			$model = self::for_project( (int) $id );

			if ( $model ) {
				$all[ (int) $id ] = $model;
			}
		}

		return $all;
	}

	/**
	 * Published projects as ID => title, newest first, for editor select controls.
	 *
	 * @return array<int, string>
	 */
	public static function project_options(): array {
		if ( null === self::$options ) {
			self::$options = array();

			foreach ( get_posts(
				array(
					'post_type'        => Projects::POST_TYPE,
					'post_status'      => 'publish',
					'posts_per_page'   => -1,
					'orderby'          => array(
						'date' => 'DESC',
						'ID'   => 'DESC',
					),
					'no_found_rows'    => true,
					'suppress_filters' => true,
				)
			) as $project ) {
				self::$options[ (int) $project->ID ] = get_the_title( $project );
			}
		}

		return self::$options;
	}

	/**
	 * Volumes from the project's own study-model widget (source "custom"), found by walking its Elementor data.
	 *
	 * @return list<array>
	 */
	private static function custom_volumes( int $post_id ): array {
		$data = get_post_meta( $post_id, '_elementor_data', true );

		if ( is_string( $data ) && '' !== $data ) {
			$data = json_decode( $data, true );
		}

		if ( ! is_array( $data ) ) {
			return array();
		}

		$settings = self::find_widget( $data );

		return $settings ? self::normalise( (array) ( $settings['volumes'] ?? array() ) ) : array();
	}

	/**
	 * Settings of the first study-model widget with a custom source and at least one volume, depth first.
	 */
	private static function find_widget( array $elements ): ?array {
		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			if ( 'widget' === ( $element['elType'] ?? '' ) && 'forma-study-model' === ( $element['widgetType'] ?? '' ) ) {
				$settings = (array) ( $element['settings'] ?? array() );

				if ( 'custom' === ( $settings['source'] ?? '' ) && ! empty( $settings['volumes'] ) ) {
					return $settings;
				}
			}

			$found = self::find_widget( (array) ( $element['elements'] ?? array() ) );

			if ( $found ) {
				return $found;
			}
		}

		return null;
	}

	private static function size( mixed $value ): float {
		return round( min( 40, max( 0.02, is_numeric( $value ) ? (float) $value : 1 ) ), 3 );
	}

	private static function coordinate( mixed $value ): float {
		return round( min( 40, max( -40, is_numeric( $value ) ? (float) $value : 0 ) ), 3 );
	}
}
