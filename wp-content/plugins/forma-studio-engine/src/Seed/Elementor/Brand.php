<?php

namespace Forma\Engine\Seed\Elementor;

use Forma\Engine\Seed\Content;

defined( 'ABSPATH' ) || exit;

/**
 * The FORMA logo files, shipped in the child theme (`assets/img/`), as Media Library attachments: the logo for light
 * surfaces, its light version for the Ink band, and the F mark for the site icon. Imported once (found again by their
 * `_forma_seed` key), so the Kit's Site Identity and the header and footer can reference them like any other image.
 */
final class Brand {

	private const FILES = array(
		'logo'  => array( 'forma-logo.png', 'FORMA' ),
		'light' => array( 'forma-logo-light.png', 'FORMA' ),
		'mark'  => array( 'forma-mark.png', 'FORMA mark' ),
	);

	/** @var array<string, int> */
	private static array $ids = array();

	/**
	 * Attachment ids by role (`logo`, `light`, `mark`), importing any that are missing.
	 *
	 * @return array<string, int>
	 */
	public static function ids(): array {
		if ( self::$ids ) {
			return self::$ids;
		}

		foreach ( self::FILES as $role => [ $file, $alt ] ) {
			self::$ids[ $role ] = self::find( $role ) ?: self::import( $role, $file, $alt );
		}

		return self::$ids;
	}

	/** WordPress's own logo and site icon (used by themes, feeds and the browser tab) point at the FORMA files too. */
	public static function apply_site_identity(): void {
		$ids = self::ids();

		set_theme_mod( 'custom_logo', $ids['logo'] );
		update_option( 'site_icon', $ids['mark'] );
	}

	private static function find( string $role ): int {
		$ids = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_key'       => Content::SEED_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery -- seed lookup, CLI only.
				'meta_value'     => 'brand:' . $role, // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);

		return $ids ? (int) $ids[0] : 0;
	}

	private static function import( string $role, string $file, string $alt ): int {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$source = get_stylesheet_directory() . '/assets/img/' . $file;

		if ( ! is_readable( $source ) ) {
			throw new \RuntimeException( esc_html( "Logo file missing: {$source}" ) );
		}

		// media_handle_sideload() moves the file, so hand it a copy.
		$tmp = wp_tempnam( $file );
		copy( $source, $tmp );

		$id = media_handle_sideload(
			array(
				'name'     => $file,
				'tmp_name' => $tmp,
			),
			0,
			$alt
		);

		if ( is_wp_error( $id ) ) {
			throw new \RuntimeException( esc_html( $file . ': ' . $id->get_error_message() ) );
		}

		update_post_meta( $id, '_wp_attachment_image_alt', $alt );
		update_post_meta( $id, Content::SEED_KEY, 'brand:' . $role );

		return (int) $id;
	}
}
