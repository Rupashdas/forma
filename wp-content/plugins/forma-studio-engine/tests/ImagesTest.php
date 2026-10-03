<?php

namespace Forma\Engine\Tests;

use Forma\Engine\Seed\Content;
use Forma\Engine\Seed\Images;

defined( 'ABSPATH' ) || exit;

final class ImagesTest extends TestCase {

	private string $dir;
	private array $created = array();

	protected function set_up(): void {
		( new Content( static function ( string $message ): void {} ) )->run();

		$this->dir = get_temp_dir() . 'forma-images-test';
		wp_mkdir_p( $this->dir );

		$image = imagecreatetruecolor( 3000, 2000 );
		imagefill( $image, 0, 0, imagecolorallocate( $image, 180, 170, 160 ) );
		imagejpeg( $image, $this->dir . '/test-house.jpg', 80 );
	}

	protected function tear_down(): void {
		foreach ( array_unique( $this->created ) as $id ) {
			Images::delete( $id );
		}

		wp_delete_file( $this->dir . '/test-house.jpg' );
	}

	public function test_delete_removes_the_file_and_every_size(): void {
		$id    = $this->import();
		$file  = get_attached_file( $id );
		$stem  = pathinfo( $file, PATHINFO_FILENAME );
		$files = static fn() => preg_grep( '/^' . preg_quote( $stem, '/' ) . '(-\d+x\d+)?\.webp$/', array_map( 'basename', glob( dirname( $file ) . '/*.webp' ) ) );

		$this->assert_same( 5, count( $files() ), 'original + 4 sizes on disk' );

		Images::delete( $id );

		$this->assert_same( 0, count( $files() ), 'nothing left on disk' );
		$this->assert_true( null === get_post( $id ), 'attachment post deleted' );
	}

	public function test_import_converts_resizes_attaches_and_credits(): void {
		$id = $this->import();

		$casa = ( new Content( static function ( string $message ): void {} ) )->project_id( 'casa-nera' );
		$meta = wp_get_attachment_metadata( $id );

		$this->assert_same( 'image/webp', get_post_mime_type( $id ) );
		$this->assert_same( 2400, $meta['width'] ?? null, 'longest edge capped at 2400' );

		$sizes = array_keys( $meta['sizes'] ?? array() );
		sort( $sizes );
		$this->assert_same( array( 'forma-960', 'large', 'medium', 'thumbnail' ), $sizes, 'only the sizes the layouts use' );

		$this->assert_same( $casa, (int) get_post_field( 'post_parent', $id ) );
		$this->assert_same( 'Test alt text', get_post_meta( $id, '_wp_attachment_image_alt', true ) );
		$this->assert_same( 'Test caption', wp_get_attachment_caption( $id ) );
		$this->assert_same( 'Test Photographer', get_post_meta( $id, Images::CREDIT_KEY, true )['name'] ?? null );
		$this->assert_same( $id, (int) get_post_thumbnail_id( $casa ) );
	}

	public function test_import_is_idempotent(): void {
		$first  = $this->import();
		$second = $this->import();

		$this->assert_same( $first, $second );
		$this->assert_same( $first, Images::attachment_id( 'test-house.jpg' ) );
	}

	public function test_missing_file_is_reported(): void {
		try {
			( new Images( $this->dir, static function ( string $message ): void {}, array( $this->entry( 'missing.jpg' ) ) ) )->run();
			$this->fail( 'expected an exception' );
		} catch ( \RuntimeException $e ) {
			$this->assert_true( str_contains( $e->getMessage(), 'missing.jpg' ), 'message names the file' );
		}
	}

	private function import(): int {
		( new Images( $this->dir, static function ( string $message ): void {}, array( $this->entry( 'test-house.jpg' ) ) ) )->run();

		$id              = Images::attachment_id( 'test-house.jpg' );
		$this->created[] = $id;

		return $id;
	}

	private function entry( string $file ): array {
		return array(
			'file'    => $file,
			'for'     => 'project:casa-nera',
			'role'    => 'featured',
			'alt'     => 'Test alt text',
			'caption' => 'Test caption',
			'credit'  => array(
				'name'   => 'Test Photographer',
				'url'    => 'https://unsplash.com/photos/test',
				'source' => 'Unsplash',
			),
		);
	}
}
