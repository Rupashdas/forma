<?php

namespace Forma\Engine\Cli;

use Forma\Engine\Seed\Content;
use Forma\Engine\Seed\Images;
use Forma\Engine\Seed\Setup;
use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the FORMA site from the plugin's data files. Every step is safe to rerun.
 *
 * ## EXAMPLES
 *
 *     wp forma all
 *     wp forma setup
 *     wp forma content
 */
final class Command {

	/**
	 * Configure site, media, permalink and Elementor settings.
	 */
	public function setup(): void {
		( new Setup( $this->logger() ) )->run();
		WP_CLI::success( 'Site configured.' );
	}

	/**
	 * Create project types, the eleven projects, pages, the front page and menus.
	 */
	public function content(): void {
		( new Content( $this->logger() ) )->run();
		WP_CLI::success( 'Content seeded.' );
	}

	/**
	 * Import the approved photographs listed in data/images.php.
	 *
	 * [--dir=<path>]
	 * : Folder holding the downloaded originals. Defaults to images-src/ next to the site's app/ folder.
	 */
	public function images( array $args, array $assoc ): void {
		$dir   = $assoc['dir'] ?? dirname( ABSPATH, 2 ) . '/images-src';
		$count = ( new Images( $dir, $this->logger() ) )->run();
		WP_CLI::success( sprintf( '%d images imported or refreshed.', $count ) );
	}

	/**
	 * Run every build step in order.
	 */
	public function all(): void {
		$this->setup();
		$this->content();
		$this->images( array(), array() );
	}

	private function logger(): \Closure {
		return static function ( string $message ): void {
			WP_CLI::log( $message );
		};
	}
}
