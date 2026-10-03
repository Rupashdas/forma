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

	public function test_render_produces_dark_lines_on_paper_at_the_same_size(): void {
		Drawings::render( $this->source, $this->target );

		$this->assert_true( is_readable( $this->target ), 'drawing written' );

		$drawing = imagecreatefromwebp( $this->target );
		$this->assert_same( 400, imagesx( $drawing ) );
		$this->assert_same( 300, imagesy( $drawing ) );

		$sum  = 0;
		$dark = 0;

		for ( $y = 0; $y < 300; $y += 2 ) {
			for ( $x = 0; $x < 400; $x += 2 ) {
				$value = ( imagecolorat( $drawing, $x, $y ) >> 8 ) & 0xFF;
				$sum  += $value;
				$dark += $value < 128 ? 1 : 0;
			}
		}

		$this->assert_true( $sum / 30000 > 200, 'mostly paper' );
		$this->assert_true( $dark > 50, 'has dark line pixels' );
		$this->assert_true( $dark < 6000, 'lines, not filled shapes' );
	}

	public function test_drawing_is_ink_on_chalk(): void {
		$ink    = array( 0x16, 0x19, 0x17 );
		$chalk  = array( 0xF2, 0xEF, 0xE8 );
		$rgb    = static fn( int $c ): array => array( ( $c >> 16 ) & 0xFF, ( $c >> 8 ) & 0xFF, $c & 0xFF );
		$sketch = Drawings::sketch( $this->source );

		$this->assert_same( $chalk, $rgb( imagecolorat( $sketch, 20, 20 ) ), 'paper is Chalk' );

		$darkest = $chalk;
		for ( $y = 0; $y < 300; $y++ ) {
			for ( $x = 0; $x < 400; $x++ ) {
				$pixel = $rgb( imagecolorat( $sketch, $x, $y ) );
				if ( array_sum( $pixel ) < array_sum( $darkest ) ) {
					$darkest = $pixel;
				}
			}
		}

		$this->assert_true( array_sum( $darkest ) < 200, 'has dark lines' );
		foreach ( $ink as $i => $floor ) {
			$this->assert_true( $darkest[ $i ] >= $floor, 'lines never go darker than Ink: ' . implode( ',', $darkest ) );
		}

		// The encoded file keeps the paper colour (lossy WebP may shift it a few levels).
		Drawings::render( $this->source, $this->target );
		$paper = $rgb( imagecolorat( imagecreatefromwebp( $this->target ), 20, 20 ) );
		$this->assert_true( max( array_map( static fn( $a, $b ) => abs( $a - $b ), $chalk, $paper ) ) <= 6, 'WebP paper stays Chalk: ' . implode( ',', $paper ) );
	}
}
