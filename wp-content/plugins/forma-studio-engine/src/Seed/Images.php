<?php

namespace Forma\Engine\Seed;

use Forma\Engine\Media\Media;

defined( 'ABSPATH' ) || exit;

/**
 * Imports the approved photographs listed in data/images.php.
 *
 * Each original is resized to the 2400px cap and converted to WebP before upload, so the media library never
 * holds a heavy JPEG original. Galleries use WordPress's own attachment parent and menu order; credits live in
 * hidden `_forma_credit` meta and feed the Colophon page. Safe to rerun: files upload once, metadata refreshes.
 */
final class Images {

	public const CREDIT_KEY = '_forma_credit';

	private array $manifest;

	public function __construct( private string $dir, private \Closure $log, ?array $manifest = null ) {
		$this->manifest = $manifest ?? require FORMA_ENGINE_PATH . 'data/images.php';
	}

	public function run(): int {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$content = new Content( static function ( string $message ): void {} );
		$order   = array();

		foreach ( $this->manifest as $image ) {
			$parent           = $this->owner( $content, $image['for'] );
			$order[ $parent ] = ( $order[ $parent ] ?? 0 ) + 1;
			$id               = $this->import( $image, $parent, $order[ $parent ] );

			if ( 'featured' === $image['role'] && $parent ) {
				set_post_thumbnail( $parent, $id );
			}

			( $this->log )( sprintf( '%s → #%d', $image['file'], $id ) );
		}

		return count( $this->manifest );
	}

	public static function attachment_id( string $file ): int {
		$ids = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_key'       => Content::SEED_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery -- seed lookup, CLI only.
				'meta_value'     => 'image:' . $file, // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);

		return $ids ? (int) $ids[0] : 0;
	}

	/**
	 * Delete an attachment and every generated size.
	 *
	 * Under WP-CLI on Windows, ABSPATH uses forward slashes and core's path_is_absolute() rejects "C:/…",
	 * so wp_delete_attachment() removes the main file but leaves the resized copies. Remove those by name first.
	 */
	public static function delete( int $id ): void {
		$file = get_attached_file( $id );
		$meta = wp_get_attachment_metadata( $id );

		if ( $file && is_array( $meta ) ) {
			foreach ( $meta['sizes'] ?? array() as $size ) {
				wp_delete_file( trailingslashit( dirname( $file ) ) . $size['file'] );
			}
		}

		wp_delete_attachment( $id, true );
	}

	private function owner( Content $content, string $for ): int {
		[ $kind, $slug ] = array_pad( explode( ':', $for, 2 ), 2, '' );

		return match ( $kind ) {
			'project' => $content->project_id( $slug ),
			'page'    => $content->page_id( $slug ),
			default   => 0,
		};
	}

	private function import( array $image, int $parent, int $order ): int {
		$id = self::attachment_id( $image['file'] );

		if ( ! $id ) {
			$source = trailingslashit( $this->dir ) . $image['file'];

			if ( ! is_readable( $source ) ) {
				throw new \RuntimeException( esc_html( 'Missing image file: ' . $image['file'] . ' in ' . $this->dir ) );
			}

			$file = array(
				'name'     => sanitize_file_name( pathinfo( $image['file'], PATHINFO_FILENAME ) . '.webp' ),
				'tmp_name' => $this->webp( $source ),
			);

			$id = media_handle_sideload( $file, $parent );

			if ( is_wp_error( $id ) ) {
				throw new \RuntimeException( esc_html( $image['file'] . ': ' . $id->get_error_message() ) );
			}

			update_post_meta( $id, Content::SEED_KEY, 'image:' . $image['file'] );
		}

		wp_update_post(
			array(
				'ID'           => $id,
				'post_parent'  => $parent,
				'menu_order'   => $order,
				'post_title'   => $image['alt'],
				'post_excerpt' => $image['caption'],
			)
		);
		update_post_meta( $id, '_wp_attachment_image_alt', $image['alt'] );
		update_post_meta( $id, self::CREDIT_KEY, $image['credit'] );

		return (int) $id;
	}

	/**
	 * A WebP copy of the original, longest edge capped at Media::MAX_EDGE, in the temp directory.
	 */
	private function webp( string $source ): string {
		$editor = wp_get_image_editor( $source );

		if ( is_wp_error( $editor ) ) {
			throw new \RuntimeException( esc_html( basename( $source ) . ': ' . $editor->get_error_message() ) );
		}

		$size = $editor->get_size();

		if ( max( $size['width'], $size['height'] ) > Media::MAX_EDGE ) {
			$editor->resize( Media::MAX_EDGE, Media::MAX_EDGE );
		}

		$editor->set_quality( 82 );
		$saved = $editor->save( get_temp_dir() . 'forma-' . wp_generate_password( 8, false ) . '.webp', 'image/webp' );

		if ( is_wp_error( $saved ) ) {
			throw new \RuntimeException( esc_html( basename( $source ) . ': ' . $saved->get_error_message() ) );
		}

		return $saved['path'];
	}
}
