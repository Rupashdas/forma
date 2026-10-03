<?php

namespace Forma\Engine\Tests;

use Forma\Engine\Seed\Drawings;

defined( 'ABSPATH' ) || exit;

final class DrawingsTest extends TestCase {

	private string $source;
	private string $target;

	protected function set_up(): void {
		$this->source = get_temp_dir() . 'forma-drawing-source.png';
		$this->target = get_temp_dir() . 'forma-drawing-target.webp';

		$image = imagecreatetruecolor( 400, 300 );
		imagefill( $image, 0, 0, imagecolorallocate( $image, 200, 195, 190 ) );
		imagefilledrectangle( $image, 100, 80, 300, 220, imagecolorallocate( $image, 40, 40, 40 ) );
		imagepng( $image, $this->source );
	}

	protected function tear_down(): void {
		wp_delete_file( $this->source );
		wp_delete_file( $this->target );
	}

	public function test_render_produces_dark_lines_on_white_at_the_same_size(): void {
		Drawings::render( $this->source, $this->target );

		$this->assert_true( is_readable( $this->target ), 'drawing written' );

		$drawing = imagecreatefromwebp( $this->target );
		$this->assert_same( 400, imagesx( $drawing ) );
		$this->assert_same( 300, imagesy( $drawing ) );

		$sum  = 0;
		$dark = 0;

		for ( $y = 0; $y < 300; $y += 2 ) {
			for ( $x = 0; $x < 400; $x += 2 ) {
				$value = imagecolorat( $drawing, $x, $y ) & 0xFF;
				$sum  += $value;
				$dark += $value < 128 ? 1 : 0;
			}
		}

		$this->assert_true( $sum / 30000 > 200, 'mostly paper-white' );
		$this->assert_true( $dark > 50, 'has dark line pixels' );
		$this->assert_true( $dark < 6000, 'lines, not filled shapes' );
	}
}
