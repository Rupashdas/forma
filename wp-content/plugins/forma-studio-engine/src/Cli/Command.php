<?php

namespace Forma\Engine\Cli;

use Forma\Engine\Seed\Content;
use Forma\Engine\Seed\Drawings;
use Forma\Engine\Seed\Elementor\Builder;
use Forma\Engine\Seed\Elementor\Design;
use Forma\Engine\Seed\Elementor\Lab;
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
	 * Generate the line drawings for projects with a Drawing / Built comparison.
	 *
	 * [--force]
	 * : Regenerate drawings that already exist.
	 */
	public function drawings( array $args, array $assoc ): void {
		$made = ( new Drawings( $this->logger() ) )->run( isset( $assoc['force'] ) );
		WP_CLI::success( sprintf( '%d drawings generated.', $made ) );
	}

	/**
	 * Build the private "Widget lab" page that shows every Forma widget and Motion effect, for browser QA.
	 */
	public function lab(): void {
		( new Lab( $this->logger() ) )->build();
		WP_CLI::success( 'Widget lab built.' );
	}

	/**
	 * Build the Elementor design from code: the Kit (colours, fonts, theme style), the saved components, the Theme
	 * Builder documents and the Home page. A document that was edited in Elementor since it was seeded is skipped
	 * unless --force is given.
	 *
	 * [--only=<steps>]
	 * : Comma-separated steps to run, in build order: kit, components, menu, header, footer, home.
	 *
	 * [--force]
	 * : Overwrite documents that were edited in Elementor.
	 *
	 * ## EXAMPLES
	 *
	 *     wp forma design
	 *     wp forma design --only=header,footer
	 *     wp forma design --force
	 */
	public function design( array $args, array $assoc ): void {
		$only = array_filter( array_map( 'trim', explode( ',', (string) ( $assoc['only'] ?? '' ) ) ) );

		Builder::force( isset( $assoc['force'] ) );

		try {
			( new Design( $this->logger() ) )->run( $only );
		} catch ( \InvalidArgumentException $e ) {
			WP_CLI::error( $e->getMessage() );
		} finally {
			Builder::force( false );
		}

		WP_CLI::success( 'Design built.' );
	}

	/**
	 * Run every build step in order.
	 */
	public function all(): void {
		$this->setup();
		$this->content();
		$this->images( array(), array() );
		$this->drawings( array(), array() );
		$this->design( array(), array() );
	}

	private function logger(): \Closure {
		return static function ( string $message ): void {
			WP_CLI::log( $message );
		};
	}
}
