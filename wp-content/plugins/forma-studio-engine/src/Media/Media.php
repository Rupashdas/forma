<?php

namespace Forma\Engine\Media;

use Forma\Engine\Contracts\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Leaner uploads for inode-limited hosting: generate only the sizes the layouts request
 * (150 thumbnail, 480 medium, 960, 1600 large, originals capped at 2400) and write them as WebP.
 */
final class Media implements Module {

	public const MAX_EDGE = 2400;

	/** Sizes no FORMA template or Elementor layout requests. */
	private const UNUSED_SIZES = array( 'medium_large', '1536x1536', '2048x2048' );

	public static function id(): string {
		return 'media';
	}

	public function is_available(): bool {
		return true;
	}

	public function register(): void {
		add_image_size( 'forma-960', 960, 0 );
		add_filter( 'intermediate_image_sizes_advanced', array( $this, 'drop_unused_sizes' ) );
		add_filter( 'big_image_size_threshold', static fn() => self::MAX_EDGE );
		add_filter( 'image_editor_output_format', array( $this, 'webp_output' ) );
		add_filter( 'image_size_names_choose', array( $this, 'size_names' ) );
	}

	public function drop_unused_sizes( array $sizes ): array {
		return array_diff_key( $sizes, array_flip( self::UNUSED_SIZES ) );
	}

	/**
	 * @param array<string, string> $formats Source mime type => output mime type.
	 */
	public function webp_output( array $formats ): array {
		if ( wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
			$formats['image/jpeg'] = 'image/webp';
			$formats['image/png']  = 'image/webp';
		}

		return $formats;
	}

	/**
	 * Offer the 960 size in the editor's size pickers (Elementor's Image widget reads this list).
	 */
	public function size_names( array $names ): array {
		return $names + array( 'forma-960' => __( 'Half width (960)', 'forma-studio-engine' ) );
	}
}
