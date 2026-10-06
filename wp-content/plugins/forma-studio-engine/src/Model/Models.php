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
 * A volume is array{kind, w, h, d, x, y, z, rot, material, part, stage[, windows]}; see data/models.php for the units.
 * A model may also ask for `trees` and `people`: how many stylised trees the runtime plants on the free edges of the plinth, and how
 * many scale figures (1.8 m, simple capsules) it stands near the buildings (both default 0; phones show none).
 */
final class Models {

	public const KINDS     = array( 'box', 'gable', 'cylinder', 'slab', 'wire' );
	public const MATERIALS = array( 'foam', 'shade', 'ink', 'timber', 'stone', 'ground', 'metal', 'leaf', 'water', 'glass', 'wire' );

	/** Most trees, and most scale figures, a model may ask for. */
	public const MAX_TREES  = 6;
	public const MAX_PEOPLE = 8;
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
	 * @return array{volumes: list<array>, camera?: array{distance?: float}, trees?: int, people?: int}|null
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

				if ( self::trees( $recipe['trees'] ?? 0 ) > 0 ) {
					$normalised['trees'] = self::trees( $recipe['trees'] );
				}

				if ( self::people( $recipe['people'] ?? 0 ) > 0 ) {
					$normalised['people'] = self::people( $recipe['people'] );
				}

				self::$recipes[ (string) $name ] = $normalised;
			}
		}

		return self::$recipes[ $key ] ?? null;
	}

	/**
	 * Cast a list of volumes (from a recipe or from widget settings) into the shape the runtime reads: numbers cast
	 * and sizes clamped to 0.02–40, kind and material limited to the allowed sets, at most 30 volumes. `windows` is
	 * kept only when it is false (a box that must stay blank); otherwise the runtime decides from the box's size.
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

			$row = array(
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

			if ( isset( $volume['windows'] ) && ! filter_var( $volume['windows'], FILTER_VALIDATE_BOOLEAN ) ) {
				$row['windows'] = false;
			}

			$out[] = $row;
		}

		return $out;
	}

	/**
	 * The model a project shows: its own custom volumes, else the recipe for its slug.
	 *
	 * @return array{id: int, slug: string, title: string, url: string, number: string, volumes: list<array>, camera?: array{distance?: float}, trees?: int, people?: int}|null
	 */
	public static function for_project( int $post_id ): ?array {
		$post = get_post( $post_id );

		if ( ! $post || Projects::POST_TYPE !== $post->post_type ) {
			return null;
		}

		$custom  = self::custom_model( $post_id );
		$volumes = $custom['volumes'];
		$camera  = array();
		$trees   = 0;
		$people  = 0;

		if ( $volumes ) {
			$trees  = $custom['trees'];
			$people = $custom['people'];

			if ( $custom['distance'] > 0 ) {
				$camera = array( 'distance' => self::distance( $custom['distance'] ) );
			}
		} else {
			$recipe  = self::recipe( $post->post_name );
			$volumes = $recipe['volumes'] ?? array();
			$camera  = $recipe['camera'] ?? array();
			$trees   = $recipe['trees'] ?? 0;
			$people  = $recipe['people'] ?? 0;
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

		if ( $trees > 0 ) {
			$model['trees'] = $trees;
		}

		if ( $people > 0 ) {
			$model['people'] = $people;
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
	 * The project's own model: the volumes and the camera distance of its study-model widget (source "custom"), found
	 * by walking its Elementor data. The volumes are empty when the project has no such widget.
	 *
	 * @return array{volumes: list<array>, distance: float, trees: int, people: int}
	 */
	private static function custom_model( int $post_id ): array {
		$none = array(
			'volumes'  => array(),
			'distance' => 0.0,
			'trees'    => 0,
			'people'   => 0,
		);
		$data = get_post_meta( $post_id, '_elementor_data', true );

		if ( is_string( $data ) && '' !== $data ) {
			$data = json_decode( $data, true );
		}

		if ( ! is_array( $data ) ) {
			return $none;
		}

		$settings = self::find_widget( $data );

		if ( ! $settings ) {
			return $none;
		}

		return array(
			'volumes'  => self::normalise( (array) ( $settings['volumes'] ?? array() ) ),
			'distance' => is_numeric( $settings['distance'] ?? null ) ? (float) $settings['distance'] : 0.0,
			'trees'    => self::trees( $settings['trees'] ?? 0 ),
			'people'   => self::people( $settings['people'] ?? 0 ),
		);
	}

	/** A number of scale figures, held to what the runtime stands (0 to MAX_PEOPLE). */
	public static function people( mixed $value ): int {
		return (int) min( self::MAX_PEOPLE, max( 0, is_numeric( $value ) ? (int) $value : 0 ) );
	}

	/** A number of trees, held to what the runtime plants (0 to MAX_TREES). */
	public static function trees( mixed $value ): int {
		return (int) min( self::MAX_TREES, max( 0, is_numeric( $value ) ? (int) $value : 0 ) );
	}

	/** A camera distance multiplier, held to the range the runtime frames well. */
	public static function distance( float $value ): float {
		return round( min( 3, max( 0.3, $value ) ), 3 );
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
