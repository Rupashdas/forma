<?php

namespace Forma\Engine\Seed;

defined( 'ABSPATH' ) || exit;

/**
 * The "drawing" half of the Drawing / Built comparison, rendered from the project's own photograph:
 * greyscale, colour-dodged with its blurred negative (a pencil-sketch technique), then mid-tones pushed to paper.
 */
final class Drawings {

	public function __construct( private \Closure $log ) {}

	public function run( bool $force = false ): int {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$content  = new Content( static function ( string $message ): void {} );
		$projects = array_filter( require FORMA_ENGINE_PATH . 'data/projects.php', static fn( array $p ) => $p['drawing'] );
		$made     = 0;

		foreach ( $projects as $project ) {
			$parent   = $content->project_id( $project['slug'] );
			$photo    = (int) get_post_thumbnail_id( $parent );
			$existing = self::attachment_id( $project['slug'] );

			if ( ! $photo ) {
				( $this->log )( "{$project['slug']}: no featured image yet, skipped." );
				continue;
			}

			if ( $existing && ! $force ) {
				continue;
			}

			if ( $existing ) {
				Images::delete( $existing );
			}

			$large  = image_get_intermediate_size( $photo, 'large' );
			$source = $large ? path_join( wp_upload_dir()['basedir'], $large['path'] ) : wp_get_original_image_path( $photo );
			$target = get_temp_dir() . "forma-drawing-{$project['slug']}.webp";

			self::render( $source, $target );

			$id = media_handle_sideload(
				array(
					'name'     => "{$project['slug']}-drawing.webp",
					'tmp_name' => $target,
				),
				$parent
			);

			if ( is_wp_error( $id ) ) {
				throw new \RuntimeException( esc_html( $project['slug'] . ': ' . $id->get_error_message() ) );
			}

			$alt = 'Line drawing of ' . $project['title'];
			wp_update_post(
				array(
					'ID'         => $id,
					'menu_order' => 99,
					'post_title' => $alt,
				)
			);
			update_post_meta( $id, '_wp_attachment_image_alt', $alt );
			update_post_meta( $id, Content::SEED_KEY, 'drawing:' . $project['slug'] );

			++$made;
			( $this->log )( "{$project['slug']}: drawing #{$id}." );
		}

		return $made;
	}

	public static function attachment_id( string $slug ): int {
		$ids = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_key'       => Content::SEED_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery -- seed lookup, CLI only.
				'meta_value'     => 'drawing:' . $slug, // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);

		return $ids ? (int) $ids[0] : 0;
	}

	public static function render( string $source, string $target ): void {
		$photo = imagecreatefromstring( (string) file_get_contents( $source ) );

		if ( false === $photo ) {
			throw new \RuntimeException( esc_html( 'Cannot read image: ' . basename( $source ) ) );
		}

		$width  = imagesx( $photo );
		$height = imagesy( $photo );
		imagefilter( $photo, IMG_FILTER_GRAYSCALE );

		// Blurred negative: shrink 8x and scale back up, a fast wide blur.
		$small = imagescale( $photo, max( 1, intdiv( $width, 8 ) ), max( 1, intdiv( $height, 8 ) ), IMG_BILINEAR_FIXED );
		imagefilter( $small, IMG_FILTER_NEGATE );
		$blur = imagescale( $small, $width, $height, IMG_BILINEAR_FIXED );

		$out = imagecreatetruecolor( $width, $height );

		for ( $y = 0; $y < $height; $y++ ) {
			for ( $x = 0; $x < $width; $x++ ) {
				$base  = imagecolorat( $photo, $x, $y ) & 0xFF;
				$top   = imagecolorat( $blur, $x, $y ) & 0xFF;
				$dodge = $top >= 255 ? 255 : min( 255, intdiv( $base * 255, 255 - $top ) );
				$value = $dodge >= 235 ? 255 : (int) max( 0, ( $dodge - 90 ) * 255 / 145 );

				imagesetpixel( $out, $x, $y, ( $value << 16 ) | ( $value << 8 ) | $value );
			}
		}

		imagewebp( $out, $target, 82 );
	}
}
