<?php

namespace Forma\Engine\Tests;

defined( 'ABSPATH' ) || exit;

final class MediaTest extends TestCase {

	public function test_unused_sizes_are_not_generated(): void {
		$sizes = apply_filters(
			'intermediate_image_sizes_advanced',
			array_fill_keys( array( 'thumbnail', 'medium', 'medium_large', 'large', '1536x1536', '2048x2048', 'forma-960' ), array() ),
			array(),
			0
		);

		$this->assert_same( array( 'thumbnail', 'medium', 'large', 'forma-960' ), array_keys( $sizes ) );
	}

	public function test_960_size_is_registered(): void {
		$this->assert_true( has_image_size( 'forma-960' ), 'forma-960 is registered' );
		$this->assert_same( 960, wp_get_additional_image_sizes()['forma-960']['width'] ?? null );
	}

	public function test_originals_are_capped_at_2400px(): void {
		$this->assert_same( 2400, apply_filters( 'big_image_size_threshold', 2560, array( 4000, 3000 ), '', 0 ) );
	}

	public function test_jpeg_and_png_are_saved_as_webp(): void {
		$formats = apply_filters( 'image_editor_output_format', array(), '', 'image/jpeg' );

		$this->assert_same( 'image/webp', $formats['image/jpeg'] ?? null );
		$this->assert_same( 'image/webp', $formats['image/png'] ?? null );
	}
}
