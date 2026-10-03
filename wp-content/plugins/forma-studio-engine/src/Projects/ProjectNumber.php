<?php

namespace Forma\Engine\Projects;

defined( 'ABSPATH' ) || exit;

/**
 * A project's sheet number ("07"): its position among published projects, oldest completion date first.
 * Derived on the fly, so adding or re-dating a project renumbers everything without stored data.
 */
final class ProjectNumber {

	/** @var array<int, int>|null Post ID => 1-based position, memoised per request. */
	private static ?array $positions = null;

	public static function for_post( int $post_id ): string {
		$positions = self::positions();

		return isset( $positions[ $post_id ] ) ? sprintf( '%02d', $positions[ $post_id ] ) : '';
	}

	public static function flush(): void {
		self::$positions = null;
	}

	/**
	 * @return array<int, int>
	 */
	private static function positions(): array {
		if ( null === self::$positions ) {
			$ids = get_posts(
				array(
					'post_type'        => Projects::POST_TYPE,
					'post_status'      => 'publish',
					'posts_per_page'   => -1,
					'orderby'          => array(
						'date' => 'ASC',
						'ID'   => 'ASC',
					),
					'fields'           => 'ids',
					'no_found_rows'    => true,
					'suppress_filters' => true,
				)
			);

			self::$positions = array();

			foreach ( array_values( $ids ) as $index => $id ) {
				self::$positions[ (int) $id ] = $index + 1;
			}
		}

		return self::$positions;
	}
}
