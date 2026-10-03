<?php

namespace Forma\Engine\Seed\Elementor;

use Forma\Engine\Seed\Content;

defined( 'ABSPATH' ) || exit;

/**
 * Finds and creates the Elementor library documents the site is built from: Theme Builder header and footer, the
 * menu popup, saved components. Each carries a `_forma_seed` marker (`template:{key}`), so reruns update the same
 * document instead of adding another.
 */
final class Templates {

	private static bool $saved = false;

	/** The library post seeded under `$key`, or 0. */
	public static function id( string $key ): int {
		$ids = get_posts(
			array(
				'post_type'      => 'elementor_library',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_key'       => Content::SEED_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery -- seed lookup, CLI only.
				'meta_value'     => "template:{$key}", // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);

		return $ids ? (int) $ids[0] : 0;
	}

	/**
	 * Create the document if it is missing, then save its elements and settings. Theme Builder conditions are saved
	 * together with the content, so a document someone has re-conditioned in Elementor keeps its conditions.
	 *
	 * @param string $key        Seed key, e.g. `header`.
	 * @param string $type       Document type: header, footer, popup, container, loop-item …
	 * @param array  $conditions Theme Builder conditions, each like `array( 'include', 'general' )`.
	 * @return int The document's post id, also when the edit guard skipped the save (see {@see self::saved()}).
	 */
	public static function upsert( string $key, string $type, string $title, array $elements, array $settings = array(), array $conditions = array() ): int {
		Builder::as_admin();

		$id = self::id( $key );

		if ( ! $id ) {
			$document = \Elementor\Plugin::$instance->documents->create(
				$type,
				array(
					'post_title'  => $title,
					'post_status' => 'publish',
				),
				array( Content::SEED_KEY => "template:{$key}" )
			);

			$id = (int) $document->get_main_id();
		}

		self::$saved = Builder::save( $id, $elements, $settings );

		if ( self::$saved && $conditions ) {
			\ElementorPro\Modules\ThemeBuilder\Module::instance()->get_conditions_manager()->save_conditions( $id, $conditions );
		}

		return $id;
	}

	/** Whether the last {@see self::upsert()} saved, false when the edit guard skipped it. */
	public static function saved(): bool {
		return self::$saved;
	}
}
